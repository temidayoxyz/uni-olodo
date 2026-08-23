<?php

namespace App\Services;

use App\Enums\ApplicationStatus;
use App\Enums\UserRole;
use App\Models\AcademicSession;
use App\Models\Application;
use App\Models\AuditLog;
use App\Models\Invoice;
use App\Models\Programme;
use App\Models\StudentProfile;
use App\Models\User;
use App\Notifications\PortalNotice;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Owns the application state machine. Controllers orchestrate; this service
 * decides. Illegal transitions throw — they are never silently coerced.
 */
class ApplicationService
{
    public function submit(Application $application): Application
    {
        $this->assertCanTransition($application, ApplicationStatus::Submitted);

        $incomplete = $this->submissionBlockers($application);

        if ($incomplete !== []) {
            throw ValidationException::withMessages([
                'application' => 'The application is not complete yet: '.implode(', ', $incomplete).'.',
            ]);
        }

        $application->forceFill([
            'status' => ApplicationStatus::Submitted,
            'submitted_at' => now(),
        ])->save();

        AuditLog::record('application.submitted', $application, ['number' => $application->number]);

        return $application;
    }

    public function withdraw(Application $application): Application
    {
        $this->assertCanTransition($application, ApplicationStatus::Withdrawn);

        $application->forceFill(['status' => ApplicationStatus::Withdrawn])->save();

        AuditLog::record('application.withdrawn', $application);

        return $application;
    }

    /** Officer actions: start review, request information, decide. */
    public function review(Application $application, User $officer, ApplicationStatus $target, ?string $note = null): Application
    {
        $this->assertCanTransition($application, $target);
        $this->assertOfficer($officer);

        if (in_array($target, [ApplicationStatus::Accepted, ApplicationStatus::ConditionallyAccepted, ApplicationStatus::Waitlisted, ApplicationStatus::Rejected], true) && trim((string) $note) === '') {
            throw ValidationException::withMessages([
                'decision_note' => 'A note is required when making a decision.',
            ]);
        }

        if ($target === ApplicationStatus::MoreInfoRequired && trim((string) $note) === '') {
            throw ValidationException::withMessages([
                'decision_note' => 'Explain what the applicant must provide.',
            ]);
        }

        $application->forceFill([
            'status' => $target,
            'decision_note' => $note,
            'decision_at' => $target->isDecided() ? now() : null,
            'decided_by' => $target->isDecided() ? $officer->id : null,
        ])->save();

        AuditLog::record('application.'.$target->value, $application, [
            'by' => $officer->email,
            'note' => $note,
        ]);

        return $application;
    }

    /** The applicant accepts an offer — this is the moment they become a student. */
    public function acceptOffer(Application $application): Application
    {
        $this->assertCanTransition($application, ApplicationStatus::Enrolled);

        return DB::transaction(function () use ($application): Application {
            $programme = $application->choices()->orderBy('rank')->first()?->programme
                ?? throw ValidationException::withMessages(['offer' => 'No programme choice is attached to this offer.']);

            $user = $application->applicant;

            $application->forceFill([
                'status' => ApplicationStatus::Enrolled,
                'offer_responded_at' => now(),
            ])->save();

            // Role transition: applicant → student. Server-side truth, never a UI switch.
            $user->forceFill(['role' => UserRole::Student])->save();

            $profile = $this->createStudentProfile($user, $programme, $application);

            $invoice = $this->raiseTuitionInvoice($user, $programme, $profile);

            $user->notify(new PortalNotice(
                title: 'Welcome to University of Olodo',
                body: "Your offer has been accepted and you are now a student ({$profile->matric_number}). Your first tuition instalment is due {$invoice->due_at?->format('j F Y')}.",
                url: '/student',
            ));

            AuditLog::record('application.offer_accepted', $application, [
                'matric_number' => $profile->matric_number,
                'programme' => $programme->code,
            ]);

            return $application;
        });
    }

    public function declineOffer(Application $application): Application
    {
        if (! $application->statusIs(ApplicationStatus::Accepted, ApplicationStatus::ConditionallyAccepted)) {
            throw ValidationException::withMessages([
                'offer' => 'There is no open offer to decline.',
            ]);
        }

        $application->forceFill([
            'status' => ApplicationStatus::Rejected,
            'offer_responded_at' => now(),
            'decision_note' => trim(($application->decision_note ?? '')."\n\nOffer declined by the applicant on ".now()->format('j F Y').'.'),
        ])->save();

        AuditLog::record('application.offer_declined', $application);

        return $application;
    }

    /**
     * Everything that must be true before an application may be submitted.
     *
     * @return array<int, string> human-readable blockers, empty when complete
     */
    public function submissionBlockers(Application $application): array
    {
        $blockers = [];

        $personalFields = ['first_name', 'last_name', 'date_of_birth', 'gender', 'phone', 'address'];
        foreach ($personalFields as $field) {
            if (in_array(trim((string) $application->{$field}), ['', '0'], true) || $application->{$field} === null) {
                $blockers[] = 'personal details';
                break;
            }
        }

        if (in_array(trim((string) $application->qualification), ['', '0'], true) || $application->examination_year === null) {
            $blockers[] = 'educational background';
        }

        if ($application->choices()->count() === 0) {
            $blockers[] = 'programme choice';
        }

        $requiredDocuments = ['passport_photograph', 'olevel_result', 'birth_certificate'];
        $uploadedTypes = $application->documents()->pluck('type')->all();
        foreach ($requiredDocuments as $type) {
            if (! in_array($type, $uploadedTypes, true)) {
                $blockers[] = 'required document: '.str_replace('_', ' ', $type);
            }
        }

        return $blockers;
    }

    private function assertCanTransition(Application $application, ApplicationStatus $target): void
    {
        if (! $application->status->canTransitionTo($target)) {
            throw ValidationException::withMessages([
                'status' => "An application in state [{$application->status->label()}] cannot move to [{$target->label()}].",
            ]);
        }
    }

    private function assertOfficer(User $user): void
    {
        if (! in_array($user->role, [UserRole::AdmissionsOfficer, UserRole::Registrar, UserRole::SuperAdmin], true)) {
            abort(403);
        }
    }

    private function createStudentProfile(User $user, Programme $programme, Application $application): StudentProfile
    {
        $session = $application->intakeSession ?? AcademicSession::current();
        $admissionYear = $session ? (int) substr($session->name, 0, 4) : (int) date('Y');
        $deptCode = $programme->department->code;

        // Matric numbers are sequential per department per admission year: UO/CSC/26/0031
        $seq = StudentProfile::query()
            ->where('matric_number', 'like', "UO/{$deptCode}/".($admissionYear % 100).'/%')
            ->count() + 31;

        do {
            $matric = sprintf('UO/%s/%02d/%04d', $deptCode, $admissionYear % 100, $seq++);
        } while (StudentProfile::where('matric_number', $matric)->exists());

        return StudentProfile::create([
            'user_id' => $user->id,
            'matric_number' => $matric,
            'programme_id' => $programme->id,
            'level' => 100,
            'status' => 'active',
            'admitted_session_id' => $session?->id,
        ]);
    }

    private function raiseTuitionInvoice(User $user, Programme $programme, StudentProfile $profile): Invoice
    {
        $firstInstalment = (int) round($programme->tuition_per_session * 0.6);

        $invoice = Invoice::create([
            'user_id' => $user->id,
            'number' => 'INV-'.date('y').'-'.str_pad((string) (Invoice::max('id') + 1 + 1000), 5, '0', STR_PAD_LEFT),
            'type' => 'tuition',
            'title' => 'Tuition — first instalment ('.$programme->name.')',
            'academic_session_id' => $profile->admitted_session_id,
            'amount_due' => $firstInstalment,
            'due_at' => now()->addWeeks(6),
            'status' => 'unpaid',
        ]);

        $invoice->items()->create([
            'description' => 'Tuition, 60% first instalment — '.$programme->name.' (100 level)',
            'quantity' => 1,
            'unit_amount' => $firstInstalment,
        ]);

        return $invoice;
    }
}
