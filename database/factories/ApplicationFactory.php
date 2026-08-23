<?php

namespace Database\Factories;

use App\Enums\ApplicationStatus;
use App\Enums\UserRole;
use App\Models\Application;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Application>
 */
class ApplicationFactory extends Factory
{
    public function definition(): array
    {
        [$first, $last] = [fake('en_NG')->firstName(), fake('en_NG')->lastName()];

        return [
            'user_id' => User::factory()->role(UserRole::Applicant)->unverified(),
            'number' => 'UOA-2026-'.str_pad((string) random_int(1, 99999), 6, '0', STR_PAD_LEFT),
            'intake_session_id' => null,
            'first_name' => $first,
            'last_name' => $last,
            'other_names' => null,
            'date_of_birth' => fake()->dateTimeBetween('-24 years', '-16 years'),
            'gender' => fake()->randomElement(['male', 'female']),
            'phone' => '080'.fake()->numerify('########'),
            'address' => fake('en_NG')->address(),
            'nationality' => 'Nigeria',
            'state_of_origin' => fake()->randomElement(['Oyo', 'Lagos', 'Kano', 'Anambra', 'Rivers', 'Kaduna']),
            'qualification' => fake()->randomElement(['waec', 'neco']),
            'examination_year' => (int) date('Y') - 1,
            'previous_school' => fake()->city().' Grammar School',
            'personal_statement' => null,
            'status' => ApplicationStatus::Draft->value,
        ];
    }
}
