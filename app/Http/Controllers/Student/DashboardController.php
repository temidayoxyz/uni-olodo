<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Assignment;
use App\Models\CourseOffering;
use App\Models\Invoice;
use App\Models\Quiz;
use App\Models\Semester;
use App\Support\GradeScale;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $user = auth()->user()->load('studentProfile.programme.department.faculty');
        $semester = Semester::where('is_current', true)->with('session')->first();

        // --- Enrolled offerings this semester (approved registrations) ------------
        $offerings = CourseOffering::query()
            ->select('course_offerings.*')
            ->join('registration_items', 'registration_items.course_offering_id', '=', 'course_offerings.id')
            ->join('registrations', 'registrations.id', '=', 'registration_items.registration_id')
            ->where('registrations.student_id', $user->id)
            ->where('registrations.semester_id', $semester?->id)
            ->where('registrations.status', 'approved')
            ->where('registration_items.status', 'registered')
            ->with(['course', 'lecturer', 'schedules'])
            ->get();

        $offeringIds = $offerings->pluck('id');

        // --- Today & next class ----------------------------------------------------
        $todayIso = now()->dayOfWeekIso;
        $allSlots = $offerings->flatMap(fn ($o) => $o->schedules->map(fn ($s) => (object) [
            'offering' => $o, 'schedule' => $s,
        ]))->sortBy([['schedule.weekday', 'asc'], ['schedule.starts_at', 'asc']])->values();

        $todaySlots = $allSlots->filter(fn ($slot) => $slot->schedule->weekday === $todayIso)->values();
        $nextSlot = $allSlots->first(fn ($slot) => $slot->schedule->weekday > $todayIso)
            ?? ($allSlots->isNotEmpty() ? $allSlots->first() : null); // wraps to next week

        // --- Deadlines: assignments due soon / quizzes opening soon -----------------
        $deadlines = collect();

        if ($offeringIds->isNotEmpty()) {
            $assignments = Assignment::query()
                ->whereIn('course_offering_id', $offeringIds)
                ->whereNotNull('published_at')
                ->where('due_at', '>=', now())
                ->whereDoesntHave('submissions', fn ($q) => $q->where('student_id', $user->id))
                ->with('offering.course')
                ->orderBy('due_at')
                ->take(4)
                ->get();

            foreach ($assignments as $assignment) {
                $deadlines->push((object) [
                    'kind' => 'assignment',
                    'title' => $assignment->title,
                    'course' => $assignment->offering->course->code,
                    'at' => $assignment->due_at,
                    'url' => '/student/courses/'.$assignment->course_offering_id.'/assignments/'.$assignment->id,
                ]);
            }

            $quizzes = Quiz::query()
                ->whereIn('course_offering_id', $offeringIds)
                ->whereNotNull('published_at')
                ->where('available_from', '<=', now()->addDays(7))
                ->where('available_until', '>=', now())
                ->whereDoesntHave('attempts', fn ($q) => $q->where('student_id', $user->id))
                ->with('offering.course')
                ->orderBy('available_from')
                ->take(2)
                ->get();

            foreach ($quizzes as $quiz) {
                $deadlines->push((object) [
                    'kind' => 'quiz',
                    'title' => $quiz->title,
                    'course' => $quiz->offering->course->code,
                    'at' => $quiz->available_until,
                    'url' => '/student/courses/'.$quiz->course_offering_id.'/quizzes/'.$quiz->id,
                ]);
            }

            $deadlines = $deadlines->sortBy('at')->values();
        }

        // --- Recent returned feedback ------------------------------------------------
        $recentFeedback = DB::table('assignment_submissions')
            ->join('assignments', 'assignments.id', '=', 'assignment_submissions.assignment_id')
            ->whereIn('assignments.course_offering_id', $offeringIds)
            ->where('assignment_submissions.student_id', $user->id)
            ->whereNotNull('assignment_submissions.graded_at')
            ->orderByDesc('assignment_submissions.graded_at')
            ->take(3)
            ->get(['assignments.title as assignment_title', 'assignments.course_offering_id',
                'assignment_submissions.score', 'assignment_submissions.feedback',
                'assignment_submissions.graded_at']);

        // --- Academic standing: CGPA over official published results only ---------------
        $gradedRows = DB::table('published_results')
            ->join('course_offerings', 'course_offerings.id', '=', 'published_results.course_offering_id')
            ->join('courses', 'courses.id', '=', 'course_offerings.course_id')
            ->where('published_results.student_id', $user->id)
            ->get(['published_results.total', 'courses.credit_units']);

        $cgpa = GradeScale::gpa($gradedRows->map(fn ($row) => [
            'total' => (float) $row->total,
            'credit_units' => $row->credit_units,
        ]));

        $registeredCredits = $user->studentProfile?->registeredCreditsFor($semester) ?? 0;

        // --- Outstanding fees -----------------------------------------------------------
        $unpaidInvoices = Invoice::query()
            ->where('user_id', $user->id)
            ->where('status', 'unpaid')
            ->orderBy('due_at')
            ->take(1)
            ->get();

        // --- Targeted announcements -------------------------------------------------------
        $announcements = Announcement::query()->visibleTo($user)->take(3)->get();

        return view('student.dashboard', compact(
            'semester', 'offerings', 'todaySlots', 'nextSlot', 'deadlines',
            'recentFeedback', 'cgpa', 'registeredCredits', 'unpaidInvoices', 'announcements',
        ));
    }
}
