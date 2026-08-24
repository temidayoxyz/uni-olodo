<?php

namespace App\Services;

use App\Enums\OfferingStatus;
use App\Enums\RegistrationStatus;
use App\Models\AuditLog;
use App\Models\Course;
use App\Models\CourseOffering;
use App\Models\PublishedResult;
use App\Models\Registration;
use App\Models\RegistrationItem;
use App\Models\Semester;
use App\Models\StudentProfile;
use App\Models\User;
use App\Notifications\PortalNotice;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The course registration rule engine. Every add/remove/submit decision is
 * made here — the UI may pre-check rules to explain itself, but this service
 * is the boundary.
 */
class RegistrationService
{
    /** University-wide maximum registered credit load per semester. */
    public const MAX_CREDITS = 24;

    /**
     * Validate adding an offering to a student's basket WITHOUT saving.
     *
     * @return array<int, string> list of violations, empty when allowed
     */
    public function checkAdd(User $student, Semester $semester, CourseOffering $offering): array
    {
        $violations = [];

        if (! $semester->registrationIsOpen()) {
            $violations[] = 'Registration for this semester '.($semester->registration_closes_at?->isPast() ? 'closed on '.$semester->registration_closes_at->format('j F Y') : 'has not opened yet').'.';
        }

        if ($offering->status !== OfferingStatus::Open) {
            $violations[] = $offering->course->code.' is not open for registration.';
        } elseif (! $offering->hasSeatsAvailable()) {
            $violations[] = $offering->course->code.' has no seats remaining.';
        }

        if ($offering->course->level > $this->highestLevelAvailable($student)) {
            $violations[] = $offering->course->code.' is above your current study level.';
        }

        foreach ($offering->course->prerequisites as $prerequisite) {
            if (! $this->hasPassed($student, $prerequisite)) {
                $violations[] = $offering->course->code.' requires '.$prerequisite->code.' ('.$prerequisite->title.') passed first.';
            }
        }

        $basketIds = $this->basket($student, $semester)->pluck('id');
        if ($basketIds->contains($offering->id)) {
            $violations[] = 'You have already added '.$offering->course->code.'.';
        }

        // Credit cap including the prospective course.
        $prospectiveCredits = $this->basketCredits($student, $semester) + $offering->course->credit_units;
        if ($prospectiveCredits > self::MAX_CREDITS) {
            $violations[] = 'This would take you to '.$prospectiveCredits.' credits; the semester maximum is '.self::MAX_CREDITS.'.';
        }

        // Timetable clash against existing basket items (never against itself).
        foreach ($this->basket($student, $semester) as $item) {
            if ($item->is($offering)) {
                continue;
            }

            if ($clash = $this->scheduleClash($item, $offering)) {
                $violations[] = 'Timetable clash: '.$offering->course->code.' overlaps '.$clash.'.';
            }
        }

        return $violations;
    }

    /**
     * Add an offering to the student's basket (creating a draft registration
     * for the semester if needed). Refuses with a message per violation.
     */
    public function addToBasket(User $student, Semester $semester, CourseOffering $offering): RegistrationItem
    {
        $violations = $this->checkAdd($student, $semester, $offering);

        if ($violations !== []) {
            throw ValidationException::withMessages(['registration' => $violations]);
        }

        return DB::transaction(function () use ($student, $semester, $offering): RegistrationItem {
            $registration = Registration::firstOrCreate([
                'student_id' => $student->id,
                'semester_id' => $semester->id,
            ], ['status' => RegistrationStatus::Draft]);

            return $registration->items()->create([
                'course_offering_id' => $offering->id,
                'status' => 'registered',
            ]);
        });
    }

    /**
     * Remove an offering from a basket. Only draft or rejected baskets are editable;
     * approved registrations go through drop-with-approval later in the term lifecycle.
     */
    public function removeFromBasket(User $student, Semester $semester, RegistrationItem $item): void
    {
        $registration = $item->registration()->first();

        abort_unless($registration && $registration->student_id === $student->id, 403);

        if (! $registration->statusIs(RegistrationStatus::Draft, RegistrationStatus::Rejected)) {
            throw ValidationException::withMessages([
                'registration' => 'This registration has already been submitted and can no longer be edited here.',
            ]);
        }

        $item->delete();
    }

    /**
     * Submit a basket for approval. Re-runs every rule against the final basket,
     * then locks it.
     */
    public function submit(User $student, Semester $semester): Registration
    {
        $registration = Registration::query()
            ->where('student_id', $student->id)
            ->where('semester_id', $semester->id)
            ->latest()
            ->first();

        if ($registration === null || $registration->activeItems()->count() === 0) {
            throw ValidationException::withMessages([
                'registration' => 'Add at least one course before submitting.',
            ]);
        }

        if (! $registration->statusIs(RegistrationStatus::Draft, RegistrationStatus::Rejected)) {
            throw ValidationException::withMessages([
                'registration' => 'This registration is already '.$registration->status->label().'.',
            ]);
        }

        // Re-validate the whole basket from scratch.
        $basket = $this->basket($student, $semester);

        $totalCredits = $basket->sum(fn (CourseOffering $o) => $o->course->credit_units);
        $violations = [];

        if (! $semester->registrationIsOpen()) {
            $violations[] = 'The registration window is closed.';
        }

        if ($totalCredits < 1) {
            $violations[] = 'Register at least one course.';
        }

        if ($totalCredits > self::MAX_CREDITS) {
            $violations[] = "Your basket totals {$totalCredits} credits; the maximum is ".self::MAX_CREDITS.'.';
        }

        foreach ($basket as $offering) {
            $violations = array_merge(
                array_filter($this->checkAdd($student, $semester, $offering), fn ($v) => ! str_contains($v, 'already added')),
                $violations,
            );
        }

        if ($violations !== []) {
            throw ValidationException::withMessages(['registration' => array_values(array_unique($violations))]);
        }

        $registration->forceFill([
            'status' => RegistrationStatus::Submitted,
            'submitted_at' => now(),
        ])->save();

        AuditLog::record('registration.submitted', $registration, [
            'courses' => $basket->map(fn (CourseOffering $o) => $o->course->code)->all(),
            'credits' => $totalCredits,
        ]);

        return $registration;
    }

    /** Registrar action: approve or reject a submitted registration. */
    public function decide(Registration $registration, User $approver, bool $approve, ?string $note = null): Registration
    {
        if (! $registration->statusIs(RegistrationStatus::Submitted)) {
            throw ValidationException::withMessages([
                'registration' => 'Only submitted registrations can be decided.',
            ]);
        }

        $registration->forceFill([
            'status' => $approve ? RegistrationStatus::Approved : RegistrationStatus::Rejected,
            'approved_at' => $approve ? now() : null,
            'approved_by' => $approve ? $approver->id : null,
            'note' => $note,
        ])->save();

        AuditLog::record($approve ? 'registration.approved' : 'registration.rejected', $registration, [
            'by' => $approver->email,
            'note' => $note,
        ]);

        $registration->student->notify(new PortalNotice(
            title: 'Course registration '.$registration->status->label(),
            body: $approve
                ? 'Your course registration for '.$registration->semester->name.' has been approved. Your timetable is now live.'
                : trim('Your course registration was not approved. '.(string) $note),
            url: '/student/'.($approve ? 'timetable' : 'registration'),
        ));

        return $registration;
    }

    // --- queries ---------------------------------------------------------------

    /**
     * The student's active basket offerings for a semester (draft/submitted/approved).
     *
     * @return Collection<int, CourseOffering>
     */
    public function basket(User $student, Semester $semester): Collection
    {
        return CourseOffering::query()
            ->select('course_offerings.*')
            ->join('registration_items', 'registration_items.course_offering_id', '=', 'course_offerings.id')
            ->join('registrations', 'registrations.id', '=', 'registration_items.registration_id')
            ->where('registrations.student_id', $student->id)
            ->where('registrations.semester_id', $semester->id)
            ->whereIn('registrations.status', [RegistrationStatus::Draft->value, RegistrationStatus::Submitted->value, RegistrationStatus::Approved->value])
            ->where('registration_items.status', 'registered')
            ->with(['course.prerequisites', 'lecturer', 'schedules'])
            ->get();
    }

    public function basketCredits(User $student, Semester $semester): int
    {
        return $this->basket($student, $semester)
            ->sum(fn (CourseOffering $o) => $o->course->credit_units);
    }

    private function hasPassed(User $student, Course $course): bool
    {
        return PublishedResult::query()
            ->where('student_id', $student->id)
            ->whereHas('offering.course', fn ($q) => $q->whereKey($course->getKey()))
            ->where('is_passed', true)
            ->exists();
    }

    /** Students may register courses at their own level or below (carryovers included) — never above. */
    private function highestLevelAvailable(User $student): int
    {
        return (int) (StudentProfile::where('user_id', $student->id)->value('level') ?? 100);
    }

    /** Human-readable description of the first overlapping slot, if any. */
    private function scheduleClash(CourseOffering $a, CourseOffering $b): ?string
    {
        foreach ($a->schedules as $slotA) {
            foreach ($b->schedules as $slotB) {
                if ($slotA->weekday !== $slotB->weekday) {
                    continue;
                }

                if ($slotA->starts_at->lt($slotB->ends_at) && $slotB->starts_at->lt($slotA->ends_at)) {
                    return $b->course->code.' ('.$slotA->weekdayName().' '.$slotA->starts_at->format('g:i a').')';
                }
            }
        }

        return null;
    }
}
