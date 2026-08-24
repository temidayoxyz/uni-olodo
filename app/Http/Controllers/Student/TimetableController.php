<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\OfferingSchedule;
use App\Models\Semester;
use App\Services\RegistrationService;
use Illuminate\View\View;

class TimetableController extends Controller
{
    public function __construct(private readonly RegistrationService $registrations) {}

    /** Weekly timetable from APPROVED registrations only — drafts never appear. */
    public function index(): View
    {
        $student = auth()->user();
        $semester = Semester::where('is_current', true)->with('session')->first();

        $approved = $student->registrations()
            ->where('semester_id', $semester?->id)
            ->where('status', 'approved')
            ->with(['items.offering.course', 'items.offering.schedules'])
            ->first();

        $slots = collect();
        foreach ($approved?->items->where('status', 'registered') ?? [] as $item) {
            foreach ($item->offering->schedules as $schedule) {
                $slots->push((object) [
                    'offering' => $item->offering,
                    'schedule' => $schedule,
                ]);
            }
        }

        // Grid: weekday rows × time buckets, ordered.
        $slots = $slots->sortBy(fn ($slot) => [$slot->schedule->weekday, $slot->schedule->starts_at])->values();

        return view('student.timetable', [
            'semester' => $semester,
            'slots' => $slots,
            'weekdays' => OfferingSchedule::WEEKDAYS,
        ]);
    }
}
