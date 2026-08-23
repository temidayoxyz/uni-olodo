<?php

namespace Database\Factories;

use App\Enums\OfferingStatus;
use App\Enums\UserRole;
use App\Models\Course;
use App\Models\CourseOffering;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CourseOffering>
 */
class CourseOfferingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'semester_id' => Semester::factory(),
            'lecturer_id' => User::factory()->role(UserRole::Lecturer),
            'capacity' => $this->faker->optional()->numberBetween(40, 120),
            'status' => OfferingStatus::Open->value,
        ];
    }

    public function forSemester(Semester $semester): static
    {
        return $this->state(fn () => ['semester_id' => $semester->id]);
    }
}
