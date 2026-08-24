<?php

namespace Tests\Feature;

use App\Enums\ResultSubmissionStatus;
use App\Enums\UserRole;
use App\Models\Course;
use App\Models\CourseOffering;
use App\Models\Programme;
use App\Models\PublishedResult;
use App\Models\Registration;
use App\Models\Semester;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\ResultService;
use App\Support\GradeScale;
use Database\Seeders\AcademicHistorySeeder;
use Database\Seeders\AcademicStructureSeeder;
use Database\Seeders\CalendarSeeder;
use Database\Seeders\CurrentRegistrationsSeeder;
use Database\Seeders\DemoUsersSeeder;
use Database\Seeders\SupportStaffSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * The full results chain: lecturer scores → submits → registry approves →
 * publishes immutable official records. Students only ever see the last step.
 */
class ResultsLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private ResultService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AcademicStructureSeeder::class);
        $this->seed(CalendarSeeder::class);
        $this->seed(DemoUsersSeeder::class);
        $this->seed(SupportStaffSeeder::class);

        $this->service = app(ResultService::class);
    }

    /** A completed past-semester offering owned by Dr. Obi, with enrolled students. */
    private function completedOffering(int $studentCount = 3): array
    {
        $obi = User::where('email', 'c.obi@olodo.edu.ng')->firstOrFail();
        $past = Semester::query()->where('starts_on', '<', now())->where('is_current', false)
            ->orderByDesc('ends_on')->firstOrFail();

        $offering = CourseOffering::create([
            'course_id' => Course::where('code', 'CSC 201')->value('id'),
            'semester_id' => $past->id,
            'lecturer_id' => $obi->id,
            'capacity' => 60,
            'status' => 'closed',
        ]);

        $students = [];
        for ($i = 0; $i < $studentCount; $i++) {
            $student = User::factory()->role(UserRole::Student)->create();
            StudentProfile::create([
                'user_id' => $student->id,
                'matric_number' => sprintf('UO/RES/23/%04d', 100 + $i),
                'programme_id' => Programme::where('code', 'CSC-BS')->value('id'),
                'level' => 200,
            ]);
            Registration::create([
                'student_id' => $student->id,
                'semester_id' => $past->id,
                'status' => 'approved',
                'approved_at' => now(),
            ])->items()->create(['course_offering_id' => $offering->id, 'status' => 'registered']);
            $students[] = $student;
        }

        return [$obi, $offering, $students];
    }

    public function test_lecturer_saves_scores_with_bounds_and_submits_a_complete_sheet(): void
    {
        [$obi, $offering, $students] = $this->completedOffering(2);

        // Over-max entries are clamped by the service.
        $written = $this->service->saveScores($obi, $offering, [
            $students[0]->id => ['ca' => 55, 'exam' => 90],   // clamped to 40 / 60
            $students[1]->id => ['ca' => 28, 'exam' => 47],
        ]);
        $this->assertSame(2, $written);

        // Incomplete sheet (a third enrolled student without scores) blocks submission.
        $third = User::factory()->role(UserRole::Student)->create();
        Registration::create([
            'student_id' => $third->id,
            'semester_id' => $offering->semester_id,
            'status' => 'approved',
            'approved_at' => now(),
        ])->items()->create(['course_offering_id' => $offering->id, 'status' => 'registered']);

        try {
            $this->service->submit($obi, $offering);
            $this->fail('Incomplete gradebook should not submit.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('incomplete', implode(' ', $e->errors()['results']));
        }

        $this->service->saveScores($obi, $offering, [$third->id => ['ca' => 30, 'exam' => 35]]);
        $submission = $this->service->submit($obi, $offering);

        $this->assertSame(ResultSubmissionStatus::Submitted, $submission->status);
    }

    public function test_only_the_owning_lecturer_can_write_scores_or_submit(): void
    {
        [, $offering] = $this->completedOffering(1);
        $otherLecturer = User::factory()->role(UserRole::Lecturer)->create();

        $this->actingAs($otherLecturer)->get('/courses/'.$offering->id.'/gradebook')->assertForbidden();

        $this->expectException(AuthorizationException::class);
        $this->service->saveScores($otherLecturer, $offering, []);
    }

    public function test_registrar_approves_then_publishes_immutable_official_records(): void
    {
        [$obi, $offering, $students] = $this->completedOffering(2);
        $registrar = User::where('email', 'registrar@olodo.edu.ng')->firstOrFail();

        $this->service->saveScores($obi, $offering, [
            $students[0]->id => ['ca' => 32, 'exam' => 48],   // total 80 → A
            $students[1]->id => ['ca' => 18, 'exam' => 19],   // total 37 → F
        ]);
        $submission = $this->service->submit($obi, $offering);

        // Publishing before approval is refused — the chain has an order.
        try {
            $this->service->publish($submission, $registrar);
            $this->fail('Publish before approve should be refused.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('Approved', implode(' ', $e->errors()['results']));
        }

        $this->service->approve($submission, $registrar);
        $this->assertSame(ResultSubmissionStatus::Approved, $submission->fresh()->status);

        // Approved scores are locked: editing is refused as a state violation.
        try {
            $this->service->saveScores($obi, $offering, [$students[0]->id => ['ca' => 10, 'exam' => 10]]);
            $this->fail('Approved scores should be locked.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('locked', implode(' ', $e->errors()['results']));
        }
    }

    public function test_publish_writes_official_records_students_can_see_and_nobody_can_alter(): void
    {
        [$obi, $offering, $students] = $this->completedOffering(2);
        $registrar = User::where('email', 'registrar@olodo.edu.ng')->firstOrFail();

        $this->service->saveScores($obi, $offering, [
            $students[0]->id => ['ca' => 32, 'exam' => 48],   // 80 → A5
            $students[1]->id => ['ca' => 18, 'exam' => 19],   // 37 → F0
        ]);
        $submission = $this->service->submit($obi, $offering);
        $this->service->approve($submission, $registrar);
        $this->service->publish($submission, $registrar);

        $this->assertSame(2, PublishedResult::count());

        $pass = PublishedResult::where('student_id', $students[0]->id)->firstOrFail();
        $fail = PublishedResult::where('student_id', $students[1]->id)->firstOrFail();

        $this->assertSame('A', $pass->grade_letter);
        $this->assertTrue($pass->is_passed);
        $this->assertSame('F', $fail->grade_letter);
        $this->assertFalse($fail->is_passed);

        // The model layer refuses mutations of published records.
        $this->expectException(\RuntimeException::class);
        $pass->total = 99.0;
        $pass->save();
    }

    public function test_students_see_published_results_with_honest_gpa_and_are_blocked_from_others(): void
    {
        // Zainab's seeded world gives her published history.
        $this->seed(CurrentRegistrationsSeeder::class);
        $this->seed(AcademicHistorySeeder::class);

        $zainab = User::where('email', 'z.adeyemi@student.olodo.edu.ng')->firstOrFail();

        $page = $this->actingAs($zainab)->get('/student/results')
            ->assertOk()
            ->assertSee('Cumulative GPA');

        // Her CGPA must match the honest computation from her published rows.
        $expected = GradeScale::gpa(
            DB::table('published_results')
                ->join('course_offerings', 'course_offerings.id', '=', 'published_results.course_offering_id')
                ->join('courses', 'courses.id', '=', 'course_offerings.course_id')
                ->where('published_results.student_id', $zainab->id)
                ->get(['published_results.total', 'courses.credit_units'])
                ->map(fn ($r) => ['total' => (float) $r->total, 'credit_units' => $r->credit_units]),
        );
        $page->assertSee(number_format($expected, 2));

        // Transcript renders and is clearly unofficial.
        $this->get('/student/transcript')
            ->assertOk()
            ->assertSee('Unofficial');

        // Another student's results are never visible through this student's views.
        $other = User::where('email', 'd.okon@student.olodo.edu.ng')->firstOrFail();
        $page = $this->actingAs($zainab)->get('/student/results');
        $this->assertStringNotContainsString($other->name, $page->getContent());
    }

    public function test_return_for_corrections_requires_a_note_and_reopens_editing(): void
    {
        [$obi, $offering, $students] = $this->completedOffering(1);
        $registrar = User::where('email', 'registrar@olodo.edu.ng')->firstOrFail();

        $this->service->saveScores($obi, $offering, [$students[0]->id => ['ca' => 20, 'exam' => 25]]);
        $submission = $this->service->submit($obi, $offering);

        try {
            $this->service->returnForCorrections($submission, $registrar, '   ');
            $this->fail('Empty note should be rejected.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('note', $e->errors());
        }

        $this->service->returnForCorrections($submission, $registrar, 'CA column exceeds moderation sheet.');

        $this->assertSame(ResultSubmissionStatus::Returned, $submission->fresh()->status);

        // Returned state permits edits again, and resubmission clears the review fields.
        $this->service->saveScores($obi, $offering, [$students[0]->id => ['ca' => 22, 'exam' => 28]]);
        $resubmitted = $this->service->submit($obi, $offering);
        $this->assertSame(ResultSubmissionStatus::Submitted, $resubmitted->status);
        $this->assertNull($resubmitted->reviewed_by);
    }

    public function test_non_registrars_cannot_touch_the_approval_queue(): void
    {
        foreach ([UserRole::AdmissionsOfficer, UserRole::Lecturer] as $role) {
            $user = User::factory()->role($role)->create();
            $this->actingAs($user)->get('/admin/results')->assertForbidden();
        }

        $registrar = User::factory()->role(UserRole::Registrar)->create();
        $this->actingAs($registrar)->get('/admin/results')->assertOk();
    }
}
