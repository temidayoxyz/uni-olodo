<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The authorization matrix: role middleware is the route boundary,
 * and no role reaches another role's portal.
 */
class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public static function portalProvider(): array
    {
        return [
            'applicant portal' => ['/applicant', UserRole::Applicant],
            'student portal' => ['/student', UserRole::Student],
            'lecturer portal' => ['/lecturer', UserRole::Lecturer],
            'admin portal' => ['/admin', UserRole::SuperAdmin],
        ];
    }

    #[DataProvider('portalProvider')]
    public function test_guests_are_redirected_to_login(string $portal): void
    {
        $this->get($portal)->assertRedirect(route('login'));
    }

    #[DataProvider('portalProvider')]
    public function test_the_matching_role_enters_its_portal(string $portal, UserRole $role): void
    {
        $user = User::factory()->role($role)->create();

        $this->actingAs($user)
            ->get($portal)
            ->assertOk();
    }

    public function test_students_cannot_enter_other_portals(): void
    {
        $student = User::factory()->role(UserRole::Student)->create();

        $this->actingAs($student);
        $this->get('/lecturer')->assertForbidden();
        $this->get('/admin')->assertForbidden();
        $this->get('/applicant')->assertForbidden();
    }

    public function test_lecturers_cannot_enter_administration(): void
    {
        $lecturer = User::factory()->role(UserRole::Lecturer)->create();

        $this->actingAs($lecturer)->get('/admin')->assertForbidden();
    }

    public function test_admissions_officer_enters_administration(): void
    {
        $officer = User::factory()->role(UserRole::AdmissionsOfficer)->create();

        $this->actingAs($officer)->get('/admin')->assertOk();
    }

    public function test_super_admin_passes_every_portal(): void
    {
        $admin = User::factory()->role(UserRole::SuperAdmin)->create();

        $this->actingAs($admin);
        $this->get('/admin')->assertOk();
        $this->get('/student')->assertForbidden(); // super_admin governs, it does not impersonate
    }
}
