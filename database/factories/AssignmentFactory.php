<?php

namespace Database\Factories;

use App\Models\Assignment;
use App\Models\CourseOffering;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Assignment>
 */
class AssignmentFactory extends Factory
{
    public function definition(): array
    {
        $due = now()->addDays($this->faker->numberBetween(3, 21));

        return [
            'course_offering_id' => CourseOffering::factory(),
            'title' => $this->faker->sentence(4),
            'instructions' => $this->faker->paragraphs(2, true),
            'points' => 100,
            'available_from' => now()->subDays($this->faker->numberBetween(5, 14)),
            'due_at' => $due,
            'late_until' => null,
            'published_at' => now()->subDays($this->faker->numberBetween(6, 15)),
        ];
    }

    /** Due soon (within days) — used to demo dashboard urgency. */
    public function dueSoon(int $days): static
    {
        return $this->state(fn () => [
            'available_from' => now()->subDays(7),
            'due_at' => now()->addDays($days),
            'published_at' => now()->subDays(8),
        ]);
    }

    public function pastDue(): static
    {
        return $this->state(fn () => [
            'available_from' => now()->subDays(30),
            'due_at' => now()->subDays(10),
            'published_at' => now()->subDays(32),
        ]);
    }
}
