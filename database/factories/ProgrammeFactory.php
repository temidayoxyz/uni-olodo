<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\Programme;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Programme>
 */
class ProgrammeFactory extends Factory
{
    public function definition(): array
    {
        $field = $this->faker->unique()->words(2, true);

        return [
            'department_id' => Department::factory(),
            'name' => 'B.Sc. '.str($field)->title(),
            'code' => Str::upper(Str::substr(Str::slug($field, ''), 0, 6)).'-BS',
            'slug' => 'bsc-'.Str::slug($field),
            'award' => 'bsc',
            'duration_semesters' => 8,
            'description' => null,
            'entry_requirements' => null,
            'tuition_per_session' => 35_000_000, // ₦350,000.00 in kobo
            'is_active' => true,
        ];
    }

    /** @param string $code Catalogue code, e.g. "CSC-BS" */
    public function withCode(string $code): static
    {
        return $this->state(fn () => ['code' => $code]);
    }
}
