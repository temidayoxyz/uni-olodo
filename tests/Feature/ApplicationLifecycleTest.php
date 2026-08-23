<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\UserRole;
use App\Models\AcademicSession;
use App\Models\Application;
use App\Models\Invoice;
use App\Models\Programme;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\ApplicationService;
use Database\Seeders\AcademicStructureSeeder;
use Database\Seeders\CalendarSeeder;
use Database\Seeders\SupportStaffSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The full application lifecycle: draft → submitted → review → decision →
 * offer acceptance (applicant becomes a student), plus officer document
 * verification and the audit trail.
 */
class ApplicationLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private ApplicationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AcademicStructureSeeder::class);
        $this->seed(CalendarSeeder::class);
        $this->seed(SupportStaffSeeder::class);

        Storage::fake('local');

        $this->service = app(ApplicationService::class);
    }

    public function test_applicant_creates_a_draft_and_saves_steps(): void
    {
        $applicant = User::factory()->role(UserRole::Applicant)->create();

        $this->actingAs($applicant)->post('/applicant/application/start')
            ->assertRedirect();

        $application = Application::where('user_id', $applicant->id)->firstOrFail();
        $this->assertSame('draft', $application->status->value);

        $this->post('/applicant/application/personal', [
            'first_name' => 'Ngozi', 'last_name' => 'Adeyinka', 'date_of_birth' => '2007-03-14',
            'gender' => 'female', 'phone' => '08031234567', 'address' => '12 Awolowo Road, Ibadan',
            'state_of_origin' => 'Oyo',
        ])->assertRedirect();

        $this->post('/applicant/application/education', [
            'qualification' => 'waec', 'examination_year' => 2025, 'previous_school' => 'St. Anne’s School',
        ])->assertRedirect();

        $csc = Programme::where('code', 'CSC-BS')->first();
        $dsc = Programme::where('code', 'DSC-BS')->first();

        $this->post('/applicant/application/choices', [
            'choice_1' => $csc->id, 'choice_2' => $dsc->id,
        ])->assertRedirect();

        // Same choices twice must not duplicate ranks.
        $this->post('/applicant/application/choices', ['choice_1' => $csc->id])->assertRedirect();

        $this->assertSame(1, $application->choices()->count());
        $this->assertSame(1, $application->choices()->first()->rank);
        $this->assertTrue($this->service->submissionBlockers($application->fresh()) !== []);
    }

    public function test_submission_is_blocked_until_complete(): void
    {
        $application = $this->completeDraft();

        $blockers = $this->service->submissionBlockers($application);
        $this->assertSame([], $blockers);

        $this->actingAs($application->applicant)
            ->post('/applicant/application/submit')
            ->assertRedirect(route('applicant.dashboard'));

        $application->refresh();
        $this->assertSame('submitted', $application->status->value);
        $this->assertNotNull($application->submitted_at);
    }

    public function test_officer_reviews_verifies_documents_and_decides(): void
    {
        $officer = User::factory()->role(UserRole::AdmissionsOfficer)->create();
        $application = $this->submittedApplication();

        $this->actingAs($officer)->get('/admin/admissions')
            ->assertOk()
            ->assertSee($application->number);

        // The state machine refuses to skip straight from submitted to a decision…
        $this->post('/admin/admissions/'.$application->id.'/decide', [
            'decision' => 'accepted', 'note' => 'Strong results.',
        ])->assertRedirect();
        $this->assertSame('submitted', $application->fresh()->status->value);

        // Start review.
        $this->post("/admin/admissions/{$application->id}/start-review")->assertRedirect();
        $this->assertSame('under_review', $application->fresh()->status->value);

        // Verify a pending document.
        $document = $application->documents()->where('type', 'passport_photograph')->firstOrFail();
        $this->post("/admin/admission-documents/{$document->id}/verify")->assertRedirect();
        $this->assertSame('verified', $document->fresh()->verification->value);

        // Rejecting requires a note for the applicant.
        $olevel = $application->documents()->where('type', 'olevel_result')->firstOrFail();
        $this->post("/admin/admission-documents/{$olevel->id}/reject", [])->assertSessionHasErrors('note');
        $this->post('/admin/admission-documents/'.$olevel->id.'/reject', ['note' => 'Scan is illegible.'])
            ->assertRedirect();
        $this->assertSame('rejected', $olevel->fresh()->verification->value);

        // …and once under review, acceptance without a note is refused…
        $this->post('/admin/admissions/'.$application->id.'/decide', ['decision' => 'accepted'])
            ->assertRedirect()
            ->assertSessionHas('error');
        $this->assertSame('under_review', $application->fresh()->status->value);

        // …but with one, the decision lands.
        $this->post('/admin/admissions/'.$application->id.'/decide', [
            'decision' => 'accepted', 'note' => 'Strong O-level credits and exam performance.',
        ])->assertRedirect(route('admin.admissions.show', $application));

        $application->refresh();
        $this->assertSame('accepted', $application->status->value);
        $this->assertNotNull($application->decided_by);
    }

    public function test_accepting_the_offer_makes_the_applicant_a_student(): void
    {
        $application = $this->applicationInStatus('accepted');
        $applicant = $application->applicant;

        $this->actingAs($applicant)->post('/applicant/offer/accept')
            ->assertRedirect('/student');

        $application->refresh();
        $this->assertSame('enrolled', $application->status->value);
        $this->assertSame('student', $applicant->fresh()->role->value);

        $profile = StudentProfile::where('user_id', $applicant->id)->firstOrFail();
        $this->assertSame(100, $profile->level);
        $this->assertMatchesRegularExpression('/^UO\/[A-Z]{3}\/\d{2}\/\d{4}$/', (string) $profile->matric_number);

        $invoice = Invoice::where('user_id', $applicant->id)->firstOrFail();
        $this->assertSame('unpaid', $invoice->status);
        $this->assertSame('tuition', $invoice->type->value);
        $this->assertGreaterThan(0, $invoice->amount_due);

        // The decision was audited.
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'application.offer_accepted',
            'subject_id' => $application->id,
        ]);
    }

    public function test_declining_an_offer_closes_it_without_student_creation(): void
    {
        $application = $this->applicationInStatus('accepted');

        $this->actingAs($application->applicant)->post('/applicant/offer/decline')->assertRedirect();

        $application->refresh();
        $this->assertSame('rejected', $application->status->value);
        $this->assertNull(StudentProfile::where('user_id', $application->user_id)->first());
    }

    public function test_applicants_cannot_reopen_decided_applications(): void
    {
        $application = $this->applicationInStatus('rejected');

        $this->actingAs($application->applicant)
            ->post('/applicant/application/personal', ['first_name' => 'X'])
            ->assertForbidden();
    }

    public function test_students_and_lecturers_cannot_see_the_admissions_queue(): void
    {
        foreach ([UserRole::Student, UserRole::Lecturer] as $role) {
            $user = User::factory()->role($role)->create();
            $this->actingAs($user)->get('/admin/admissions')->assertForbidden();
        }
    }

    public static function decisionProvider(): array
    {
        return [
            'more info requires a note' => ['more_info_required', null],
            'acceptance requires a note' => ['accepted', null],
            'rejection requires a note' => ['rejected', ''],
        ];
    }

    #[DataProvider('decisionProvider')]
    public function test_officer_actions_that_talk_to_applicants_require_notes(string $decision, ?string $note): void
    {
        $officer = User::factory()->role(UserRole::AdmissionsOfficer)->create();
        $application = $this->applicationInStatus('under_review');

        $payload = ['decision' => $decision];
        if ($note !== null) {
            $payload['note'] = $note;
        }

        $this->actingAs($officer)->post("/admin/admissions/{$application->id}/decide", $payload)
            ->assertRedirect();

        $this->assertSame('under_review', $application->fresh()->status->value);
    }

    // --- helpers ---------------------------------------------------------------

    /** Draft with everything filled except documents. */
    private function completeDraft(): Application
    {
        $applicant = User::factory()->role(UserRole::Applicant)->create();

        $application = Application::create([
            'user_id' => $applicant->id,
            'number' => 'UOA-TEST-000001',
            'intake_session_id' => AcademicSession::current()?->id,
            'first_name' => 'Ngozi', 'last_name' => 'Adeyinka',
            'date_of_birth' => '2007-03-14', 'gender' => 'female',
            'phone' => '08031234567', 'address' => '12 Awolowo Road, Ibadan',
            'qualification' => 'waec', 'examination_year' => 2025,
            'status' => 'draft',
        ]);

        $application->choices()->create([
            'programme_id' => Programme::where('code', 'CSC-BS')->value('id'),
            'rank' => 1,
        ]);

        foreach (['passport_photograph', 'olevel_result', 'birth_certificate'] as $type) {
            Storage::disk('local')->put("applications/{$application->id}/{$type}.pdf", '%PDF-1.4 test');
            $application->documents()->create([
                'type' => $type,
                'original_name' => "{$type}.pdf",
                'stored_path' => "applications/{$application->id}/{$type}.pdf",
                'mime_type' => 'application/pdf',
                'size_bytes' => 12000,
            ]);
        }

        return $application;
    }

    private function submittedApplication(): Application
    {
        return tap($this->completeDraft(), fn (Application $a) => $this->service->submit($a));
    }

    private function applicationInStatus(string $status): Application
    {
        $application = $this->submittedApplication();

        if ($status === 'under_review') {
            $this->service->review($application, $this->officer(), ApplicationStatus::UnderReview);
        } elseif ($status === 'accepted') {
            $this->service->review($application, $this->officer(), ApplicationStatus::UnderReview);
            $this->service->review($application, $this->officer(), ApplicationStatus::Accepted, 'Meets all criteria.');
        } elseif ($status === 'rejected') {
            $this->service->review($application, $this->officer(), ApplicationStatus::UnderReview);
            $this->service->review($application, $this->officer(), ApplicationStatus::Rejected, 'Does not meet cut-off.');
        }

        return $application->fresh();
    }

    private function officer(): User
    {
        return User::factory()->role(UserRole::AdmissionsOfficer)->create();
    }
}
