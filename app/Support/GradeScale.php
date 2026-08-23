<?php

namespace App\Support;

/**
 * The university's five-point grading scale (Nigerian convention):
 * A 70+ → 5.0, B 60–69 → 4.0, C 50–59 → 3.0, D 45–49 → 2.0, E 40–44 → 1.0, F < 40 → 0.0
 */
final class GradeScale
{
    /** @var array<int, array{min: float, letter: string, point: float}> */
    private const BANDS = [
        ['min' => 70.0, 'letter' => 'A', 'point' => 5.0],
        ['min' => 60.0, 'letter' => 'B', 'point' => 4.0],
        ['min' => 50.0, 'letter' => 'C', 'point' => 3.0],
        ['min' => 45.0, 'letter' => 'D', 'point' => 2.0],
        ['min' => 40.0, 'letter' => 'E', 'point' => 1.0],
        ['min' => 0.0, 'letter' => 'F', 'point' => 0.0],
    ];

    public static function letterFor(?float $total): ?string
    {
        return self::bandFor($total)['letter'] ?? null;
    }

    public static function pointFor(?float $total): ?float
    {
        return self::bandFor($total)['point'] ?? null;
    }

    public static function isPassed(?float $total): bool
    {
        return $total !== null && $total >= 40.0;
    }

    /**
     * Weighted GPA across graded courses (grade points × credit units ÷ total credits).
     * Courses with a failing grade still count toward GPA, per university policy.
     *
     * @param  iterable<int, array{total: ?float, credit_units: int}>  $gradedCourses
     */
    public static function gpa(iterable $gradedCourses): ?float
    {
        $qualityPoints = 0.0;
        $credits = 0;

        foreach ($gradedCourses as $course) {
            if ($course['total'] === null) {
                continue;
            }

            $points = self::pointFor($course['total']);
            if ($points === null) {
                continue;
            }

            $qualityPoints += $points * $course['credit_units'];
            $credits += $course['credit_units'];
        }

        if ($credits === 0) {
            return null;
        }

        return round($qualityPoints / $credits, 2);
    }

    /** @return array{min: float, letter: string, point: float}|null */
    private static function bandFor(?float $total): ?array
    {
        if ($total === null) {
            return null;
        }

        foreach (self::BANDS as $band) {
            if ($total >= $band['min']) {
                return $band;
            }
        }

        return null;
    }
}
