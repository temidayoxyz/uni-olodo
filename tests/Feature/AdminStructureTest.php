<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Department;
use App\Models\Programme;
use App\Models\Semester;
use App\Models\User;
use Database\Seeders\AcademicStructureSeeder;
use Database\Seeders\CalendarSeeder;
use Database\Seeders\DemoUsersSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Registry-owned structure management and super-admin user management:
 * who may enter, what persists, and what gets audited.
 */
class AdminStructureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AcademicStructureSeeder::class);
        $this->seed(CalendarSeeder::class);
        $this->seed(DemoUsersSeeder::class);
    }

    public static function areaProvider(): array
    {
        return [
            'structure overview' => ['/admin/academics'],
            'course catalogue' => ['/admin/academics/courses'],
            'calendar' => ['/admin/academics/calendar'],
        ];
    }

    #[DataProvider('areaProvider')]
    public function test_only_the_right_roles_enter_each_area(string $path): void
    {
        $registrar = User::where('email', 'registrar@olodo.edu.ng')->firstOrFail();
        $admin = User::where('email', 'admin@olodo.edu.ng')->firstOrFail();
        $lecturer = User::where('email', 'c.obi@olodo.edu.ng')->firstOrFail();
        $officer = User::where('email', 'admissions@olodo.edu.ng')->firstOrFail();

        // Structure areas: registry + super admin.
        $this->actingAs($registrar)->get($path)->assertOk();
        $this->actingAs($admin)->get($path)->assertOk();

        foreach ([$lecturer, $officer] as $outsider) {
            $this->actingAs($outsider)->get($path)->assertForbidden();
        }
    }

    public function test_users_area_is_super_admin_only(): void
    {
        $super = User::where('email', 'admin@olodo.edu.ng')->firstOrFail();
        $registrar = User::where('email', 'registrar@olodo.edu.ng')->firstOrFail();

        $this->actingAs($super)->get('/admin/users')->assertOk();
        $this->actingAs($registrar)->get('/admin/users')->assertForbidden();
    }

    public function test_registrar_creates_and_updates_a_programme(): void
    {
        $registrar = User::where('email', 'registrar@olodo.edu.ng')->firstOrFail();
        $departmentId = Department::where('code', 'BUS')->value('id');

        $this->actingAs($registrar)->post('/admin/academics/programmes', [
            'department_id' => $departmentId,
            'name' => 'B.Sc. Marketing',
            'code' => 'MKT-BS',
            'award' => 'bsc',
            'duration_semesters' => 8,
            'tuition_per_session_naira' => 320000,
            'is_active' => 1,
            'description' => 'Market-facing management education.',
        ])->assertRedirect()->assertSessionHas('status');

        $programme = Programme::where('code', 'MKT-BS')->firstOrFail();
        $this->assertSame(32_000_000, $programme->tuition_per_session); // stored in kobo
        $this->assertSame('bsc-marketing', $programme->slug);

        // Duplicate codes are rejected.
        $this->post('/admin/academics/programmes', [
            'department_id' => $departmentId,
            'name' => 'Marketing II',
            'code' => 'MKT-BS',
            'award' => 'bsc',
            'duration_semesters' => 8,
            'tuition_per_session_naira' => 1,
        ])->assertSessionHasErrors('code');

        // Update flows through the same validation.
        $this->put("/admin/academics/programmes/{$programme->id}", [
            'department_id' => $departmentId,
            'name' => 'B.Sc. Marketing',
            'code' => 'MKT-BS',
            'award' => 'bsc',
            'duration_semesters' => 8,
            'tuition_per_session_naira' => 340000,
            'description' => null,
            'entry_requirements' => null,
        ])->assertRedirect();

        $this->assertSame(34_000_000, $programme->fresh()->tuition_per_session);
    }

    public function test_course_catalogue_add_toggle_and_filter(): void
    {
        $registrar = User::where('email', 'registrar@olodo.edu.ng')->firstOrFail();
        $deptId = Department::where('code', 'CSC')->value('id');

        $this->actingAs($registrar)->post('/admin/academics/courses', [
            'department_id' => $deptId,
            'code' => 'CSC 410',
            'title' => 'Compiler Construction',
            'credit_units' => 3,
            'level' => 400,
        ])->assertRedirect()->assertSessionHas('status');

        $course = Course::where('code', 'CSC 410')->firstOrFail();
        $this->assertTrue($course->is_active);

        // Duplicate code refused.
        $this->post('/admin/academics/courses', [
            'department_id' => $deptId, 'code' => 'CSC 410', 'title' => 'Dup', 'credit_units' => 3, 'level' => 400,
        ])->assertSessionHasErrors('code');

        // Deactivate — future registration refuses it without touching history.
        $this->post("/admin/academics/courses/{$course->id}/toggle")->assertRedirect();
        $this->assertFalse($course->fresh()->is_active);

        // Department filter narrows the listing.
        $this->get('/admin/academics/courses?department='.$deptId)
            ->assertOk()
            ->assertSee('CSC 410');
    }

    public function test_registrar_moves_the_registration_window(): void
    {
        $registrar = User::where('email', 'registrar@olodo.edu.ng')->firstOrFail();
        $semester = Semester::where('is_current', true)->firstOrFail();

        $this->assertTrue($semester->registrationIsOpen());

        $this->actingAs($registrar)
            ->put("/admin/academics/calendar/semesters/{$semester->id}", [
                'registration_opens_at' => now()->subWeeks(4)->format('Y-m-d\TH:i'),
                'registration_closes_at' => now()->subWeek()->format('Y-m-d\TH:i'),
            ])->assertRedirect()->assertSessionHas('status');

        $this->assertFalse($semester->fresh()->registrationIsOpen());

        // The change was audited.
        $this->assertDatabaseHas('audit_logs', ['action' => 'calendar.window_updated']);
    }

    public function test_super_admin_changes_roles_with_audit_and_self_protection(): void
    {
        $super = User::where('email', 'admin@olodo.edu.ng')->firstOrFail();
        $lecturer = User::where('email', 'c.obi@olodo.edu.ng')->firstOrFail();

        // Promote lecturer → registrar.
        $this->actingAs($super)->put("/admin/users/{$lecturer->id}", [
            'role' => 'registrar',
            'status' => 'active',
        ])->assertRedirect()->assertSessionHas('status');

        $this->assertSame('registrar', $lecturer->fresh()->role->value);

        // Audited with from/to.
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'user.updated',
            'subject_id' => $lecturer->id,
        ]);

        // Suspension blocks the next sign-in.
        $this->actingAs($super)->put("/admin/users/{$lecturer->id}", [
            'role' => 'registrar',
            'status' => 'suspended',
        ])->assertRedirect();

        $this->post('/logout'); // ensure clean session for the suspended account

        $this->post('/login', [
            'email' => $lecturer->email,
            'password' => 'password',
        ]);
        $this->assertGuest();

        // Self-demotion is refused.
        $this->actingAs($super)->put("/admin/users/{$super->id}", [
            'role' => 'registrar',
            'status' => 'active',
        ])->assertRedirect()->assertSessionHas('error');
    }
}
