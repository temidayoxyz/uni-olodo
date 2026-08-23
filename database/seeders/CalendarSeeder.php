<?php

namespace Database\Seeders;

use App\Models\AcademicSession;
use App\Models\Semester;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Three academic sessions: two completed (history + published results) and the
 * current one, whose registration window is OPEN right now. Dates for the
 * current session are computed relative to `now`, and the historical sessions
 * are derived backwards from it, so the demo never collides whatever day it runs.
 */
class CalendarSeeder extends Seeder
{
    public function run(): void
    {
        // Monday of the week three weeks ago (or the following Monday if that lands on a Sunday).
        $base = now()->subWeeks(3);
        $start = $base->dayOfWeekIso === 7 ? $base->copy()->addDay() : $base->copy()->startOfWeek();

        $currentYear = $this->sessionStartYear($start);
        $firstEnd = $start->copy()->addWeeks(18);

        // Historical sessions, oldest first.
        foreach ([2, 1] as $yearsBack) {
            $year = $currentYear - $yearsBack;
            $this->createSession($year.'/'.($year + 1), Carbon::createFromDate($year, 9, max(1, min(9, $start->day))), false);
        }

        // Current session.
        $session = $this->createSession(
            $currentYear.'/'.($currentYear + 1),
            $start,
            true,
            endsOnOverride: $firstEnd->copy()->addWeeks(20),
            firstSemester: [
                'registration_opens_at' => now()->subDays(10),
                'registration_closes_at' => now()->addWeeks(5),
                'is_current' => true,
            ],
        );

        Semester::updateOrCreate([
            'academic_session_id' => $session->id,
            'number' => 2,
        ], [
            'name' => 'Second Semester',
            'starts_on' => $firstEnd->copy()->addWeeks(1),
            'ends_on' => $firstEnd->copy()->addWeeks(21),
            'registration_opens_at' => $firstEnd->copy()->subWeeks(2),
            'registration_closes_at' => $firstEnd->copy()->addWeeks(3),
            'is_current' => false,
        ]);
    }

    private function createSession(string $name, Carbon $startsOn, bool $current, ?Carbon $endsOnOverride = null, array $firstSemester = []): AcademicSession
    {
        AcademicSession::query()->where('is_current', true)->when(! $current, fn ($q) => $q->where('name', '!=', $name))->update(['is_current' => false]);

        $session = AcademicSession::updateOrCreate(['name' => $name], [
            'starts_on' => $startsOn,
            'ends_on' => $endsOnOverride ?? $startsOn->copy()->addMonths(10),
            'is_current' => $current,
        ]);

        foreach ([[1, 'First Semester'], [2, 'Second Semester']] as [$number, $semesterName]) {
            $semStart = $number === 1 ? $startsOn : $startsOn->copy()->addMonths(5);
            $semEnd = $semStart->copy()->addWeeks(19);

            $attributes = [
                'name' => $semesterName,
                'starts_on' => $semStart,
                'ends_on' => $semEnd,
                'registration_opens_at' => $firstSemester['registration_opens_at'] ?? $semStart->copy()->subDays(14),
                'registration_closes_at' => $firstSemester['registration_closes_at'] ?? $semStart->copy()->addWeeks(2),
                'is_current' => ($firstSemester['is_current'] ?? false) && $number === 1,
            ];

            Semester::updateOrCreate([
                'academic_session_id' => $session->id,
                'number' => $number,
            ], $attributes);
        }

        return $session;
    }

    /** The academic-session start year containing $date (sessions run Aug–Jul). */
    private function sessionStartYear(Carbon $date): int
    {
        return $date->month >= 8 ? $date->year : $date->year - 1;
    }
}
