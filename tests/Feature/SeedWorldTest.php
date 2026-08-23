<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\Application;
use App\Models\AssignmentSubmission;
use App\Models\CourseOffering;
use App\Models\Invoice;
use App\Models\PublishedResult;
use App\Models\Semester;
use App\Models\User;
use App\Support\GradeScale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The seeded world must demonstrate the product honestly: real relationships,
 * live registration window, and GPA math that checks out.
 */
class SeedWorldTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_demo_accounts_exist_with_documented_emails(): void
    {
        foreach ([
            'admin@olodo.edu.ng' => 'super_admin',
            'registrar@olodo.edu.ng' => 'registrar',
            'admissions@olodo.edu.ng' => 'admissions_officer',
            'c.obi@olodo.edu.ng' => 'lecturer',
            'z.adeyemi@student.olodo.edu.ng' => 'student',
            'emeka.nwosu@example.com' => 'applicant',
        ] as $email => $role) {
            $this->assertDatabaseHas('users', ['email' => $email, 'role' => $role]);
        }
    }

    public function test_current_semester_registration_window_is_open(): void
    {
        $semester = Semester::where('is_current', true)->firstOrFail();

        $this->assertTrue($semester->registrationIsOpen());
    }

    public function test_zainab_is_registered_for_seventeen_credits(): void
    {
        $zainab = User::where('email', 'z.adeyemi@student.olodo.edu.ng')->with('studentProfile')->firstOrFail();
        $semester = Semester::where('is_current', true)->firstOrFail();

        $credits = $zainab->studentProfile->registeredCreditsFor($semester);

        $this->assertSame(17, $credits);
    }

    public function test_zainab_cgpa_matches_her_published_history(): void
    {
        $zainab = User::where('email', 'z.adeyemi@student.olodo.edu.ng')->firstOrFail();

        $rows = DB::table('published_results')
            ->join('course_offerings', 'course_offerings.id', '=', 'published_results.course_offering_id')
            ->join('courses', 'courses.id', '=', 'course_offerings.course_id')
            ->where('published_results.student_id', $zainab->id)
            ->get(['published_results.total', 'courses.credit_units']);

        $cgpa = GradeScale::gpa($rows->map(fn ($r) => [
            'total' => (float) $r->total,
            'credit_units' => $r->credit_units,
        ]));

        // Seeded scores were chosen to land at ≈3.62 — verify the maths stays honest.
        $this->assertNotNull($cgpa);
        $this->assertEqualsWithDelta(3.62, $cgpa, 0.02);

        // Every history row flows through the official chain: no orphan results.
        $this->assertSame(
            PublishedResult::where('student_id', $zainab->id)->count(),
            $rows->count(),
        );
    }

    public function test_dr_obi_owns_two_offerings_with_a_grading_queue(): void
    {
        $obi = User::where('email', 'c.obi@olodo.edu.ng')->firstOrFail();
        $semester = Semester::where('is_current', true)->firstOrFail();

        $offerings = CourseOffering::where('lecturer_id', $obi->id)
            ->where('semester_id', $semester->id)
            ->with('assignments')
            ->get();

        $this->assertCount(2, $offerings);

        $pendingSubmissions = AssignmentSubmission::query()
            ->whereIn('assignment_id', $offerings->flatMap->assignments->pluck('id'))
            ->whereNull('graded_at')
            ->count();

        $this->assertGreaterThan(0, $pendingSubmissions);
    }

    public function test_applicants_demonstrate_distinct_workflow_states(): void
    {
        foreach (['under_review', 'draft', 'more_info_required', 'accepted'] as $state) {
            $this->assertTrue(
                Application::where('status', $state)->exists(),
                "No application seeded in state [{$state}].",
            );
        }
    }

    public function test_submitted_applicants_have_paid_application_fees(): void
    {
        $paidCount = Invoice::query()
            ->where('type', 'application_fee')
            ->where('status', 'paid')
            ->count();

        $this->assertSame(3, $paidCount); // Emeka, Aisha, Chidi
    }

    public function test_registrar_has_pending_result_approvals(): void
    {
        $this->assertDatabaseHas('result_submissions', ['status' => 'submitted']);
    }

    public function test_seeded_personas_render_their_dashboards_with_real_data(): void
    {
        // The primary demo student sees her standing, courses and notices.
        $zainab = User::where('email', 'z.adeyemi@student.olodo.edu.ng')->firstOrFail();

        $this->actingAs($zainab)->get('/student')
            ->assertOk()
            ->assertSee('Academic standing')
            ->assertSee('CSC 301');

        // Dr. Obi sees her two offerings and her grading queue.
        $obi = User::where('email', 'c.obi@olodo.edu.ng')->firstOrFail();

        $this->actingAs($obi)->get('/lecturer')
            ->assertOk()
            ->assertSee('to grade');

        // Emeka (under review) sees his live application state.
        $emeka = User::where('email', 'emeka.nwosu@example.com')->firstOrFail();

        $this->actingAs($emeka)->get('/applicant')
            ->assertOk()
            ->assertSee('Under review');

        // And the registrar enters administration.
        $registrar = User::where('email', 'registrar@olodo.edu.ng')->firstOrFail();

        $this->actingAs($registrar)->get('/admin')->assertOk();
    }

    public function test_announcement_targeting_reaches_the_right_people_only(): void
    {
        $zainab = User::where('email', 'z.adeyemi@student.olodo.edu.ng')->firstOrFail();
        $staffNotice = Announcement::where('title', 'like', 'Provisional results upload%')->firstOrFail();

        // The staff-only announcement is invisible to a student…
        $visibleToZainab = Announcement::query()
            ->visibleTo($zainab)
            ->pluck('announcements.id');

        $this->assertNotContains($staffNotice->id, $visibleToZainab);

        // …and visible to staff.
        $registrar = User::where('email', 'registrar@olodo.edu.ng')->firstOrFail();
        $visibleToRegistrar = Announcement::query()->visibleTo($registrar)->pluck('announcements.id');
        $this->assertContains($staffNotice->id, $visibleToRegistrar);

        // The CSC-301-scoped notice reaches Zainab (she's enrolled) via her offering.
        $labMove = Announcement::where('title', 'like', 'CSC 305 lab session%')->firstOrFail();
        $this->assertContains($labMove->id, $visibleToZainab);
    }
}
