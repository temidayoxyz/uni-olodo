<?php

namespace Database\Seeders;

use App\Enums\ApplicationStatus;
use App\Enums\UserRole;
use App\Models\AcademicSession;
use App\Models\Application;
use App\Models\ApplicationChoice;
use App\Models\Invoice;
use App\Models\PaymentTransaction;
use App\Models\Programme;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Applicant personas in different workflow states (docs/SEED.md):
 *   Emeka — under review (documents pending verification)
 *   Fatima — draft, wizard midway
 *   Aisha — more info requested (O-level scan illegible)
 *   Chidi — accepted, offer awaiting response
 */
class AdmissionsSeeder extends Seeder
{
    public function run(): void
    {
        $password = Hash::make('password');
        $officerId = User::where('role', UserRole::AdmissionsOfficer->value)->value('id');
        $financeId = User::where('role', UserRole::FinanceOfficer->value)->value('id');
        $csc = Programme::where('code', 'CSC-BS')->first();
        $dsc = Programme::where('code', 'DSC-BS')->first();
        $bus = Programme::where('code', 'BUS-BS')->first();
        $seq = 40;

        // --- Emeka Nwosu — under review ------------------------------------------
        $emeka = $this->application(
            'Emeka Nwosu', 'emeka.nwosu@example.com', 'male',
            '2005-04-11', 'Anambra', 'waec', 2025, 'Graceland International School, Port Harcourt',
            ApplicationStatus::UnderReview,
            choices: [$csc, $dsc],
            submittedAt: now()->subDays(21),
        );

        $this->document($emeka, 'passport_photograph', 'passport-photo.jpg', 'image/jpeg', verified: true, reviewerId: $officerId);
        $this->document($emeka, 'olevel_result', 'WAEC-result-scan.pdf', 'application/pdf', verified: false); // pending review
        $this->document($emeka, 'birth_certificate', 'birth-cert.pdf', 'application/pdf', verified: true, reviewerId: $officerId);
        $this->feeInvoice($emeka, $seq += 1, $financeId);

        // --- Fatima Bello — draft --------------------------------------------------
        $fatima = $this->application(
            'Fatima Bello', 'fatima.bello@example.com', 'female',
            '2006-09-02', 'Kano', 'neco', 2026, 'Prime Academy, Kano',
            ApplicationStatus::Draft,
            choices: [$bus],
        );
        $this->document($fatima, 'passport_photograph', 'my-photo.jpg', 'image/jpeg');

        // --- Aisha Musa — more info required --------------------------------------
        $aisha = $this->application(
            'Aisha Musa', 'aisha.musa@example.com', 'female',
            '2005-12-19', 'Kaduna', 'waec', 2025, 'Hillcrest School, Jos',
            ApplicationStatus::MoreInfoRequired,
            choices: [$csc],
            decisionNote: 'Your O-level result scan is not legible. Please upload a clear colour scan of the original certificate or statement of result.',
            decidedBy: $officerId,
            submittedAt: now()->subDays(30),
            decisionAt: now()->subDays(18),
        );

        $this->document($aisha, 'passport_photograph', 'photo.jpg', 'image/jpeg', verified: true, reviewerId: $officerId);
        $this->document($aisha, 'olevel_result', 'scan001-lowres.jpg', 'image/jpeg', rejected: true, reviewerId: $officerId, note: 'Unreadable — rescan at 300dpi.');
        $this->feeInvoice($aisha, $seq += 1, $financeId);

        // --- Chidi Anyanwu — accepted, offer open ---------------------------------
        $chidi = $this->application(
            'Chidi Anyanwu', 'chidi.anyanwu@example.com', 'male',
            '2005-07-30', 'Enugu', 'neco', 2025, 'Denis Memorial Grammar School, Onitsha',
            ApplicationStatus::Accepted,
            choices: [$bus, $csc],
            decisionNote: 'Admitted to B.Sc. Business Administration. Accept your offer through the portal to begin onboarding.',
            decidedBy: $officerId,
            submittedAt: now()->subDays(35),
            decisionAt: now()->subDays(8),
        );

        $this->document($chidi, 'passport_photograph', 'chidi-passport.jpg', 'image/jpeg', verified: true, reviewerId: $officerId);
        $this->document($chidi, 'olevel_result', 'NECO-statement.pdf', 'application/pdf', verified: true, reviewerId: $officerId);
        $this->document($chidi, 'entrance_exam_slip', 'entrance-slip.pdf', 'application/pdf', verified: true, reviewerId: $officerId);
        $this->feeInvoice($chidi, $seq += 1, $financeId);
    }

    private function application(
        string $name,
        string $email,
        string $gender,
        string $dob,
        string $state,
        string $qualification,
        int $examYear,
        string $school,
        ApplicationStatus $status,
        array $choices = [],
        ?string $decisionNote = null,
        ?int $decidedBy = null,
        ?Carbon $submittedAt = null,
        ?Carbon $decisionAt = null,
    ): Application {
        $user = User::firstOrCreate([
            'email' => $email,
        ], [
            'name' => $name,
            'email_verified_at' => $status === ApplicationStatus::Draft ? null : now(),
            'password' => Hash::make('password'),
            'role' => UserRole::Applicant->value,
        ]);

        [$firstName, $lastName] = explode(' ', $name.' ');

        $application = $user->applications()->create([
            'number' => sprintf('UOA-%s-%06d', date('Y'), random_int(1000, 9999)),
            'intake_session_id' => AcademicSession::current()?->id,
            'first_name' => trim($firstName),
            'last_name' => trim($lastName) !== '' ? trim($lastName) : $name,
            'date_of_birth' => $dob,
            'gender' => $gender,
            'phone' => '080'.Str::padLeft((string) random_int(0, 99999999), 8, '0'),
            'address' => "{$school} area, {$state}",
            'nationality' => 'Nigeria',
            'state_of_origin' => $state,
            'qualification' => $qualification,
            'examination_year' => $examYear,
            'previous_school' => $school,
            'personal_statement' => $status === ApplicationStatus::Draft ? null : 'I am applying because I want a university that takes teaching seriously. I chose University of Olodo for its student–faculty ratio and its computing facilities, and I intend to make the most of both.',
            'status' => $status->value,
            'submitted_at' => $submittedAt,
            'decision_at' => $decisionAt,
            'decided_by' => $decidedBy,
            'decision_note' => $decisionNote,
        ]);

        foreach ($choices as $index => $programme) {
            ApplicationChoice::create([
                'application_id' => $application->id,
                'programme_id' => $programme->id,
                'rank' => $index + 1,
            ]);
        }

        return $application;
    }

    private function document(Application $application, string $type, string $name, string $mime, bool $verified = false, bool $rejected = false, ?int $reviewerId = null, ?string $note = null): void
    {
        $path = "applications/{$application->id}/".Str::random(16).'-'.$name;

        if ($mime === 'application/pdf') {
            Storage::disk('local')->put($path, "%PDF-1.4\n% Seeded placeholder document.\n");
        } else {
            Storage::disk('local')->put($path, base64_decode('/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAAgGBgcGBQgHBwcJCQgKDBQNDAsLDBkSEw8UHRofGh0dHBwhISUkJCUsJyA1JTInJygnJSgpMzU1NTU1NTU1Nf/AABEIAAEAAQMBIgACEQEDEQH/xAAfAAABBQEBAQEBAQAAAAAAAAAAAQIDBAUGBwgJCgv/xAC1EAACAQMDAgQDBQUEBAAAAX0BAgMABBEFEiExQQYTUWEHInEUMoGRoQgjQrHBFVLR8CQzYnKCCQoWFxgZGiUmJygpKjU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/aAAwDAQACEQMRAD8A/vwooooA//9k='));
        }

        $application->documents()->create([
            'type' => $type,
            'original_name' => $name,
            'stored_path' => $path,
            'mime_type' => $mime,
            'size_bytes' => random_int(60_000, 1_400_000),
            'verification' => $rejected ? 'rejected' : ($verified ? 'verified' : 'pending'),
            'reviewer_note' => $note,
            'reviewed_by' => ($verified || $rejected) ? $reviewerId : null,
            'reviewed_at' => ($verified || $rejected) ? now()->subDays(random_int(10, 20)) : null,
        ]);
    }

    private function feeInvoice(Application $application, int $seq, int $financeId): void
    {
        $invoice = Invoice::create([
            'user_id' => $application->user_id,
            'number' => sprintf('INV-%s-%05d', date('y'), $seq),
            'type' => 'application_fee',
            'title' => 'Application fee — '.config('app.name'),
            'amount_due' => 1_000_000, // ₦10,000.00
            'due_at' => now()->addWeeks(2),
            'status' => 'paid',
            'paid_at' => now()->subDays(random_int(20, 34)),
        ]);

        $invoice->items()->create([
            'description' => 'Undergraduate application processing fee ('.$application->number.')',
            'quantity' => 1,
            'unit_amount' => 1_000_000,
        ]);

        PaymentTransaction::create([
            'invoice_id' => $invoice->id,
            'reference' => 'UOPAY-'.strtoupper(Str::random(12)),
            'provider' => 'manual',
            'provider_reference' => 'BANK-TELLER-'.random_int(10000, 99999),
            'amount' => 1_000_000,
            'status' => 'verified',
            'verified_at' => $invoice->paid_at,
            'verified_by' => $financeId,
            'metadata' => ['channel' => 'bank transfer', 'teller' => true],
        ]);
    }
}
