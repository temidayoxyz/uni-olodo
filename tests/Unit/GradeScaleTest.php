<?php

namespace Tests\Unit;

use App\Support\GradeScale;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class GradeScaleTest extends TestCase
{
    public static function bandProvider(): array
    {
        return [
            [100.0, 'A', 5.0],
            [70.0, 'A', 5.0],
            [69.9, 'B', 4.0],
            [60.0, 'B', 4.0],
            [59.0, 'C', 3.0],
            [50.0, 'C', 3.0],
            [49.0, 'D', 2.0],
            [45.0, 'D', 2.0],
            [44.0, 'E', 1.0],
            [40.0, 'E', 1.0],
            [39.9, 'F', 0.0],
            [12.0, 'F', 0.0],
        ];
    }

    #[DataProvider('bandProvider')]
    public function test_grades_follow_the_five_point_band(float $total, string $letter, float $point): void
    {
        $this->assertSame($letter, GradeScale::letterFor($total));
        $this->assertSame($point, GradeScale::pointFor($total));
    }

    public function test_null_totals_have_no_grade(): void
    {
        $this->assertNull(GradeScale::letterFor(null));
        $this->assertNull(GradeScale::pointFor(null));
    }

    public function test_pass_mark_is_forty(): void
    {
        $this->assertTrue(GradeScale::isPassed(40.0));
        $this->assertFalse(GradeScale::isPassed(39.5));
        $this->assertFalse(GradeScale::isPassed(null));
    }

    public function test_gpa_is_credit_weighted(): void
    {
        // 5 points × 4 units + 2 points × 1 unit = 22 quality points over 5 units.
        $courses = [
            ['total' => 80.0, 'credit_units' => 4], // A
            ['total' => 45.0, 'credit_units' => 1], // D
        ];

        $this->assertSame(4.4, GradeScale::gpa($courses));
    }

    public function test_gpa_of_nothing_is_null(): void
    {
        $this->assertNull(GradeScale::gpa([]));
        $this->assertNull(GradeScale::gpa([['total' => null, 'credit_units' => 3]]));
    }
}
