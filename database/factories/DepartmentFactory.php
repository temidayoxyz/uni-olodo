<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\Faculty;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Department>
 */
class DepartmentFactory extends Factory
{
    public function definition(): array
    {
        $name = 'Department of '.$this->faker->unique()->words(2, true);

        return [
            'faculty_id' => Faculty::factory(),
            'name' => str($name)->title()->toString(),
            'code' => Str::upper(Str::substr(Str::slug(str($name)->title(), ''), 0, 3)),
            'slug' => Str::slug($name),
            'summary' => null,
        ];
    }
}
