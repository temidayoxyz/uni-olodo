<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_renders(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('Sign in to the portal');
    }

    public function test_users_can_authenticate_and_land_in_their_portal(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $response = $this->post('/login', [
            'email' => $student->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect('/student');
    }

    public function test_applicants_are_sent_to_email_verification_before_the_portal(): void
    {
        $applicant = User::factory()->role(UserRole::Applicant)->unverified()->create();

        $this->post('/login', [
            'email' => $applicant->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();

        $this->get('/applicant')
            ->assertRedirect(route('verification.notice'));
    }

    public function test_suspended_accounts_cannot_sign_in(): void
    {
        $user = User::factory()->create(['status' => 'suspended']);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertGuest();
        $this->get('/login')->assertSee('suspended');
    }

    public function test_wrong_password_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create(['role' => 'student']);

        $this->actingAs($user)
            ->post('/logout')
            ->assertRedirect('/');

        $this->assertGuest();
    }

    public function test_registration_creates_an_unverified_applicant_only(): void
    {
        $response = $this->post('/register', [
            'name' => 'Adaeze Okonkwo',
            'email' => 'adaeze.okonkwo@example.com',
            'password' => 'secure-password-1',
            'password_confirmation' => 'secure-password-1',
        ]);

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'adaeze.okonkwo@example.com',
            'role' => 'applicant',
        ]);
        $this->assertNull(User::where('email', 'adaeze.okonkwo@example.com')->first()->email_verified_at);
        $response->assertRedirect(route('verification.notice'));
    }
}
