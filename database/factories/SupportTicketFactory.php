<?php

namespace Database\Factories;

use App\Enums\SupportTicketStatus;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SupportTicket>
 */
class SupportTicketFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->state([]),
            'category' => $this->faker->randomElement(['it', 'academic', 'admissions', 'finance', 'general']),
            'subject' => $this->faker->sentence(5),
            'status' => SupportTicketStatus::Open->value,
            'assigned_to' => null,
            'resolved_at' => null,
        ];
    }
}
