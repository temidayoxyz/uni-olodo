<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\AcademicSession;
use App\Models\Course;
use App\Models\CourseOffering;
use App\Models\CourseScore;
use App\Models\Department;
use App\Models\Programme;
use App\Models\PublishedResult;
use App\Models\Registration;
use App\Models\ResultSubmission;
use App\Models\Semester;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\RegistrationService;
use App\Support\GradeScale;
use Database\Seeders\AcademicStructureSeeder;
use Database\Seeders\CalendarSeeder;
use Database\Seeders\OfferingsSeeder;
use Database\Seeders\SupportStaffSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * The registration rule engine: every rule has a refusal case and the
 * happy path is proven end-to-end through submission and approval.
 */
class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    private RegistrationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AcademicStructureSeeder::class);
        $this->seed(CalendarSeeder::class);
        $this->seed(OfferingsSeeder::class);
        $this->seed(SupportStaffSeeder::class);

        $this->service = app(RegistrationService::class);
    }

    // --- rules ------------------------------------------------------------------

    public function test_registration_window_is_enforced(): void
    {
        $student = $this->student(200, 'CSC-BS');
        $semester = Semester::where('is_current', true)->firstOrFail();

        // Close the window.
        $semester->forceFill([
            'registration_opens_at' => now()->subWeeks(6),
            'registration_closes_at' => now()->subWeeks(1),
        ])->save();

        $offering = $this->offering('CSC 201');
        $violations = $this->service->checkAdd($student, $semester, $offering);

        $this->assertNotEmpty($violations);
        $this->assertStringContainsString('closed on', $violations[0]);
    }

    public function test_prerequisites_must_be_passed_from_published_results(): void
    {
        $student = $this->student(300, 'CSC-BS');
        $semester = Semester::where('is_current', true)->firstOrFail();
        $dsa = $this->offering('CSC 301'); // requires CSC 201

        // Without CSC 201 in history → refused.
        $violations = $this->service->checkAdd($student, $semester, $dsa);
        $this->assertNotEmpty(collect($violations)->first(fn ($v) => str_contains($v, 'CSC 201')));

        // A FAILED CSC 201 does not unlock it either.
        $this->publishHistory($student, 'CSC 201', 35);
        $violations = $this->service->checkAdd($student, $semester, $dsa);
        $this->assertNotEmpty(collect($violations)->first(fn ($v) => str_contains($v, 'CSC 201')));

        // Passing it unlocks the course.
        CourseScore::where('student_id', $student->id)->update(['exam_score' => 50]);
        PublishedResult::query()->where('student_id', $student->id)->update(['is_passed' => true]);

        $violations = $this->service->checkAdd($student, $semester, $dsa);
        $this->assertEmpty($violations, print_r($violations, true));
    }

    public function test_adding_beyond_the_credit_cap_is_refused(): void
    {
        $student = $this->student(300, 'CSC-BS');
        $semester = Semester::where('is_current', true)->firstOrFail();

        // Fill the basket to exactly the 24-credit maximum with 3-unit courses.
        for ($i = 0; $i < 8; $i++) {
            $this->service->addToBasket($student, $semester, $this->syntheticOffering('FILL '.(100 + $i), 3));
        }
        $this->assertSame(24, $this->service->basketCredits($student, $semester));

        // One more unit of any size must be refused.
        $violations = $this->service->checkAdd($student, $semester, $this->offering('CSC 303'));
        $this->assertNotEmpty(collect($violations)->first(fn ($v) => str_contains($v, 'maximum')));
    }

    public function test_submission_refuses_a_basket_over_the_credit_cap(): void
    {
        $student = $this->student(300, 'CSC-BS');
        $semester = Semester::where('is_current', true)->firstOrFail();

        $registration = Registration::create([
            'student_id' => $student->id,
            'semester_id' => $semester->id,
            'status' => 'draft',
        ]);

        // Stuff 30 credits directly into the draft, bypassing the service.
        $registration->items()->create([
            'course_offering_id' => $this->syntheticOffering('LOAD 900', 30)->id,
            'status' => 'registered',
        ]);

        try {
            $this->service->submit($student, $semester);
            $this->fail('Submission should have been refused over the credit cap.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('maximum', implode(' ', $e->errors()['registration']));
        }
    }

    public function test_duplicate_registration_is_refused(): void
    {
        $student = $this->student(300, 'CSC-BS');
        $semester = Semester::where('is_current', true)->firstOrFail();
        $this->grantPrerequisites($student);

        $offering = $this->offering('CSC 303'); // requires CSC 201 + nothing blocking

        $this->service->addToBasket($student, $semester, $offering);

        $violations = $this->service->checkAdd($student, $semester, $offering);
        $this->assertNotEmpty(collect($violations)->first(fn ($v) => str_contains($v, 'already added')));

        try {
            $this->service->addToBasket($student, $semester, $offering);
            $this->fail('Duplicate add should throw.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('already added', implode(' ', $e->errors()['registration']));
        }

        $this->assertSame(1, $this->service->basket($student, $semester)->count());
    }

    public function test_timetable_conflicts_are_detected(): void
    {
        $student = $this->student(300, 'CSC-BS');
        $semester = Semester::where('is_current', true)->firstOrFail();
        $this->grantPrerequisites($student);

        $this->service->addToBasket($student, $semester, $this->offering('CSC 301')); // Mon & Wed 10:00–12:00

        // A synthetic course holding Wednesday 10:00–12:00 clashes directly.
        $clashing = $this->syntheticOffering('CLASH 400', 3);
        $clashing->schedules()->create(['weekday' => 3, 'starts_at' => '10:30', 'ends_at' => '12:00', 'venue' => 'LT9']);

        $violations = $this->service->checkAdd($student, $semester, $clashing->fresh(['course', 'schedules']));
        $this->assertNotNull(collect($violations)->first(fn ($v) => str_contains($v, 'Timetable clash')), print_r($violations, true));

        // A non-conflicting course adds cleanly alongside.
        $violations = $this->service->checkAdd($student, $semester, $this->offering('CSC 308')); // Mon 14–16
        $this->assertEmpty($violations, print_r($violations, true));
    }

    public function test_courses_above_the_students_level_are_refused(): void
    {
        $student = $this->student(100, 'BUS-BS');
        $semester = Semester::where('is_current', true)->firstOrFail();

        $violations = $this->service->checkAdd($student, $semester, $this->offering('BUS 201'));
        $this->assertNotEmpty(collect($violations)->first(fn ($v) => str_contains($v, 'above your current study level')));
    }

    // --- end-to-end flow ----------------------------------------------------------

    public function test_basket_submit_and_registrar_approval_flow(): void
    {
        $student = $this->student(300, 'CSC-BS');
        $registrar = User::factory()->role(UserRole::Registrar)->create();

        // Prerequisites for the 300L basket come from published history.
        foreach (['CSC 201', 'CSC 202'] as $code) {
            $this->publishHistory($student, $code, 65);
        }
        CourseScore::where('student_id', $student->id)->update(['exam_score' => 45]);
        PublishedResult::query()->where('student_id', $student->id)->update(['is_passed' => true]);

        $this->actingAs($student)->post('/student/registration/add', ['offering' => $this->offering('CSC 301')->id])->assertRedirect();
        $this->post('/student/registration/add', ['offering' => $this->offering('CSC 308')->id])->assertRedirect();

        $this->post('/student/registration/submit')
            ->assertRedirect(route('student.registration'))
            ->assertSessionHas('status');

        $registration = Registration::where('student_id', $student->id)->firstOrFail();
        $this->assertSame('submitted', $registration->status->value);

        // Registrar sees it in the queue and approves.
        $this->actingAs($registrar)->get('/admin/registrations')
            ->assertOk()
            ->assertSee($student->name);

        $this->post("/admin/registrations/{$registration->id}/approve")
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertSame('approved', $registration->fresh()->status->value);
        $this->assertDatabaseHas('audit_logs', ['action' => 'registration.approved']);

        // Approval unlocks the timetable.
        $this->actingAs($student)->get('/student/timetable')
            ->assertOk()
            ->assertSee('CSC 301');
    }

    public function test_rejection_requires_a_reason_and_notifies(): void
    {
        $student = $this->student(300, 'CSC-BS');
        $registrar = User::factory()->role(UserRole::Registrar)->create();

        $this->grantPrerequisites($student);

        $this->actingAs($student)->post('/student/registration/add', ['offering' => $this->offering('CSC 301')->id]);
        $this->post('/student/registration/submit');

        $registration = Registration::where('student_id', $student->id)->firstOrFail();

        $this->actingAs($registrar)->post("/admin/registrations/{$registration->id}/reject", [])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->post("/admin/registrations/{$registration->id}/reject", ['note' => 'Credit load inconsistent with your level.'])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertSame('rejected', $registration->fresh()->status->value);

        // The student may now edit and resubmit.
        $this->actingAs($student)->get('/student/registration')->assertOk();
    }

    public function test_only_the_registrar_sees_approval_queue(): void
    {
        foreach ([UserRole::AdmissionsOfficer, UserRole::Lecturer] as $role) {
            $user = User::factory()->role($role)->create();
            $this->actingAs($user)->get('/admin/registrations')->assertForbidden();
        }

        $registrar = User::factory()->role(UserRole::Registrar)->create();
        $this->actingAs($registrar)->get('/admin/registrations')->assertOk();
    }

    public function test_submitted_baskets_are_locked_for_editing(): void
    {
        $student = $this->student(300, 'CSC-BS');
        $this->grantPrerequisites($student);

        $this->service->addToBasket($student, Semester::where('is_current', true)->first(), $this->offering('CSC 301'));

        $registration = Registration::where('student_id', $student->id)->firstOrFail();
        $registration->forceFill(['status' => 'submitted', 'submitted_at' => now()])->save();

        $item = $registration->items()->first();

        $this->actingAs($student)
            ->post('/student/registration/remove/'.$item->id)
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame(1, $registration->fresh()->items()->count());
    }

    // --- helpers -----------------------------------------------------------------

    /** Publish passing results for CSC 201 + CSC 202 so 300L CS courses unlock. */
    private function grantPrerequisites(User $student): void
    {
        foreach (['CSC 201', 'CSC 202'] as $code) {
            $this->publishHistory($student, $code, 65);
        }
        PublishedResult::query()->where('student_id', $student->id)->update(['is_passed' => true]);
    }

    private int $matricSeq = 1;

    private function student(int $level, string $programmeCode): User
    {
        $user = User::factory()->role(UserRole::Student)->create();

        StudentProfile::create([
            'user_id' => $user->id,
            'matric_number' => sprintf('UO/TST/24/%04d', $this->matricSeq++),
            'programme_id' => Programme::where('code', $programmeCode)->value('id'),
            'level' => $level,
            'admitted_session_id' => AcademicSession::current()?->id,
        ]);

        return $user;
    }

    /** A synthetic open offering for cap/filler scenarios. */
    private function syntheticOffering(string $code, int $units): CourseOffering
    {
        $course = Course::create([
            'department_id' => Department::where('code', 'CSC')->value('id'),
            'code' => $code,
            'title' => 'Synthetic course '.$code,
            'credit_units' => $units,
            'level' => 300,
        ]);

        return CourseOffering::create([
            'course_id' => $course->id,
            'semester_id' => Semester::where('is_current', true)->value('id'),
            'status' => 'open',
        ]);
    }

    private function offering(string $courseCode): CourseOffering
    {
        return CourseOffering::query()
            ->where('semester_id', Semester::where('is_current', true)->value('id'))
            ->whereHas('course', fn ($q) => $q->where('code', $courseCode))
            ->firstOrFail();
    }

    /** Give the student one passed published result for $courseCode at $total. */
    private function publishHistory(User $student, string $courseCode, float $total): void
    {
        $pastSemester = Semester::query()
            ->where('starts_on', '<', now())
            ->orderByDesc('ends_on')
            ->firstOrFail();

        $offering = CourseOffering::firstOrCreate([
            'course_id' => Course::where('code', $courseCode)->value('id'),
            'semester_id' => $pastSemester->id,
        ], [
            'lecturer_id' => User::factory()->create()->id,
            'capacity' => 60,
            'status' => 'closed',
        ]);

        [$ca, $exam] = [$total * 0.4, $total * 0.6];

        $submission = ResultSubmission::firstOrCreate([
            'course_offering_id' => $offering->id,
        ], [
            'submitted_by' => $offering->lecturer_id ?? User::factory()->create()->id,
            'status' => 'published',
            'submitted_at' => now(),
            'published_at' => now(),
        ]);

        PublishedResult::create([
            'result_submission_id' => $submission->id,
            'course_offering_id' => $offering->id,
            'semester_id' => $pastSemester->id,
            'student_id' => $student->id,
            'ca_score' => $ca,
            'exam_score' => $exam,
            'total' => $total,
            'grade_letter' => GradeScale::letterFor($total),
            'grade_point' => GradeScale::pointFor($total),
            'is_passed' => $total >= 40,
            'published_at' => now(),
        ]);
    }
}
