<?php

namespace Database\Factories;

use App\Models\Programme;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentProfile>
 */
class StudentProfileFactory extends Factory
{
    public function definition(): array
    {
        $level = $this->faker->randomElement([100, 200, 300, 400]);

        return [
            'user_id' => User::factory(),
            'matric_number' => null,
            'programme_id' => Programme::factory(),
            'level' => $level,
            'adviser_id' => null,
            'status' => 'active',
            'admitted_session_id' => null,
        ];
    }

    public function withMatric(string $matric): static
    {
        return $this->state(fn () => ['matric_number' => $matric]);
    }
}
