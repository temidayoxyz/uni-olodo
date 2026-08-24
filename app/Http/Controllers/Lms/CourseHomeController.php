<?php

namespace App\Http\Controllers\Lms;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\CourseOffering;
use App\Models\Quiz;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class CourseHomeController extends Controller
{
    public function index(CourseOffering $offering): View
    {
        Gate::authorize('view', $offering);

        $user = auth()->user();
        $managing = $user->can('manage', $offering);

        $modules = $offering->modules()
            ->whereNotNull('published_at')
            ->with(['contents' => fn ($q) => $q->whereNotNull('published_at')])
            ->get();

        // What's due / what's next — student-oriented.
        $upcoming = collect();
        if (! $managing) {
            $assignments = Assignment::query()
                ->where('course_offering_id', $offering->id)
                ->whereNotNull('published_at')
                ->with(['submissions' => fn ($q) => $q->where('student_id', $user->id)])
                ->orderBy('due_at')
                ->get();

            foreach ($assignments as $assignment) {
                $upcoming->push((object) [
                    'type' => 'assignment',
                    'model' => $assignment,
                    'submission' => $assignment->submissions->first(),
                ]);
            }

            foreach (Quiz::query()
                ->where('course_offering_id', $offering->id)
                ->whereNotNull('published_at')
                ->with(['attempts' => fn ($q) => $q->where('student_id', $user->id)])
                ->orderBy('available_until')->get() as $quiz) {
                $upcoming->push((object) [
                    'type' => 'quiz',
                    'model' => $quiz,
                    'submission' => null,
                    'attempt' => $quiz->attempts->first(),
                ]);
            }
        } else {
            $upcoming = Assignment::query()
                ->where('course_offering_id', $offering->id)
                ->whereNotNull('published_at')
                ->withCount(['submissions as pending_count' => fn ($q) => $q->whereNull('graded_at')])
                ->orderBy('due_at')->get()
                ->map(fn ($a) => (object) ['type' => 'assignment', 'model' => $a, 'submission' => null]);
        }

        return view('lms.course-home', [
            'offering' => $offering->load('course.department', 'lecturer'),
            'modules' => $modules,
            'items' => $upcoming,
            'managing' => $managing,
            'enrolledCount' => $managing ? $offering->enrolmentCount() : null,
        ]);
    }
}
