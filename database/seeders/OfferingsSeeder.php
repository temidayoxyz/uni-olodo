<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\CourseOffering;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Current-semester offerings with timetables and lecturer assignments.
 */
class OfferingsSeeder extends Seeder
{
    // course code => [lecturer email, [[weekday, start, end, venue], …]]
    private const OFFERINGS = [
        'CSC 201' => ['e.uche@olodo.edu.ng', [[2, '08:00', '10:00', 'LT3'], [4, '08:00', '10:00', 'LT3']]],
        'CSC 202' => ['a.umeh@olodo.edu.ng', [[3, '10:00', '12:00', 'LT2']]],
        'CSC 301' => ['c.obi@olodo.edu.ng', [[1, '10:00', '12:00', 'LT1'], [3, '10:00', '12:00', 'LT1']]],
        'CSC 303' => ['a.umeh@olodo.edu.ng', [[2, '14:00', '16:00', 'LT2'], [5, '14:00', '16:00', 'LT2']]],
        'CSC 304' => ['a.umeh@olodo.edu.ng', [[3, '14:00', '17:00', 'Lab 1A']]],
        'CSC 305' => ['c.obi@olodo.edu.ng', [[4, '10:00', '12:00', 'Lab 2B'], [5, '10:00', '12:00', 'Lab 2B']]],
        'CSC 308' => ['a.umeh@olodo.edu.ng', [[1, '14:00', '16:00', 'LT1']]],
        'CSC 401' => ['a.umeh@olodo.edu.ng', [[1, '08:00', '10:00', 'LT4'], [4, '14:00', '16:00', 'Lab 2A']]],
        'CSC 402' => ['a.umeh@olodo.edu.ng', [[2, '10:00', '12:00', 'LT4']]],
        'MTH 205' => ['e.uche@olodo.edu.ng', [[4, '10:00', '12:00', 'LT3'], [5, '08:00', '10:00', 'LT3']]],
        'STA 105' => ['e.uche@olodo.edu.ng', [[3, '08:00', '10:00', 'LT3']]],
        'GST 201' => ['y.ibrahim@olodo.edu.ng', [[5, '10:00', '12:00', 'LT5']]],
        'GST 301' => ['y.ibrahim@olodo.edu.ng', [[5, '16:00', '17:00', 'LT5']]],
        'BUS 101' => ['y.ibrahim@olodo.edu.ng', [[2, '10:00', '12:00', 'LT5']]],
        'BUS 201' => ['y.ibrahim@olodo.edu.ng', [[1, '08:00', '10:00', 'LT4'], [4, '08:00', '10:00', 'LT4']]],
        'ACC 101' => ['b.adeoye@olodo.edu.ng', [[1, '10:00', '12:00', 'LT6']]],
        'ACC 201' => ['b.adeoye@olodo.edu.ng', [[3, '08:00', '10:00', 'LT6'], [5, '08:00', '10:00', 'LT6']]],
        'EEE 201' => ['k.lawal@olodo.edu.ng', [[2, '14:00', '16:00', 'Eng LT1'], [4, '14:00', '16:00', 'Lab E2']]],
    ];

    public function run(): void
    {
        $semester = Semester::where('is_current', true)->firstOrFail();

        foreach (self::OFFERINGS as $code => [$lecturerEmail, $schedule]) {
            $offering = CourseOffering::create([
                'course_id' => Course::where('code', $code)->value('id'),
                'semester_id' => $semester->id,
                'lecturer_id' => User::where('email', $lecturerEmail)->value('id'),
                'capacity' => 60,
                'status' => 'open',
            ]);

            foreach ($schedule as [$weekday, $startsAt, $endsAt, $venue]) {
                $offering->schedules()->create([
                    'weekday' => $weekday,
                    'starts_at' => $startsAt,
                    'ends_at' => $endsAt,
                    'venue' => $venue,
                ]);
            }
        }
    }
}
