<?php

namespace App\Http\Controllers\Lecturer;

use App\Http\Controllers\Controller;
use App\Models\Semester;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $user = auth()->user();
        $semester = Semester::where('is_current', true)->with('session')->first();

        // My current offerings with classlists and pending grading counts.
        $offerings = $user->taughtOfferings()
            ->where('semester_id', $semester?->id)
            ->with(['course', 'schedules'])
            ->withCount(['assignments as published_assignments_count' => fn ($q) => $q->whereNotNull('published_at')])
            ->get()
            ->each(function ($offering): void {
                $offering->enrolled = $offering->enrolmentCount();
                $offering->ungraded = $offering->assignments()
                    ->whereNotNull('published_at')
                    ->withCount(['submissions as pending' => fn ($q) => $q->whereNull('graded_at')])
                    ->get()
                    ->sum('pending');
            });

        // Today's teaching slots.
        $todayIso = now()->dayOfWeekIso;
        $todaySlots = $offerings
            ->flatMap(fn ($o) => $o->schedules->where('weekday', $todayIso)->map(fn ($s) => (object) ['offering' => $o, 'schedule' => $s]))
            ->sortBy(fn ($slot) => $slot->schedule->starts_at)
            ->values();

        return view('lecturer.dashboard', compact('semester', 'offerings', 'todaySlots'));
    }
}
