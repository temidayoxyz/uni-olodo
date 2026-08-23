<?php

namespace Database\Factories;

use App\Models\CourseOffering;
use App\Models\Quiz;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Quiz>
 */
class QuizFactory extends Factory
{
    public function definition(): array
    {
        return [
            'course_offering_id' => CourseOffering::factory(),
            'title' => $this->faker->sentence(3),
            'instructions' => 'Attempt all questions. The quiz is timed; the timer starts when you begin.',
            'duration_minutes' => 20,
            'available_from' => now()->addDay(),
            'available_until' => now()->addWeek(),
            'max_attempts' => 1,
            'shuffle_questions' => false,
            'reveal_answers' => false,
            'published_at' => now(),
        ];
    }

    public function openNow(): static
    {
        return $this->state(fn () => [
            'available_from' => now()->subHours(2),
            'available_until' => now()->addDays(4),
        ]);
    }
}
