<?php

namespace App\Http\Controllers\Lms;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\CourseOffering;
use App\Notifications\PortalNotice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * The grading queue is strictly per-offering, per-assignment — the policy
 * allows only the assigned lecturer (or registrar override) through.
 */
class GradingController extends Controller
{
    public function show(CourseOffering $offering, Assignment $assignment): View
    {
        Gate::authorize('grade', [$offering, $assignment]);
        abort_unless($assignment->course_offering_id === $offering->id, 404);

        return view('lms.grading', [
            'offering' => $offering->load('course'),
            'assignment' => $assignment,
            'submissions' => $assignment->submissions()
                ->with('student.studentProfile')
                ->orderByRaw('graded_at IS NOT NULL, submitted_at')
                ->get(),
            'enrolledCount' => $offering->enrolmentCount(),
        ]);
    }

    public function store(Request $request, CourseOffering $offering, AssignmentSubmission $submission): RedirectResponse
    {
        Gate::authorize('grade', [$offering, $submission->assignment]);
        abort_unless($submission->assignment->course_offering_id === $offering->id, 404);

        $max = (int) $submission->assignment->points;

        $validated = $request->validate([
            'score' => ['required', 'numeric', 'min:0', "max:{$max}"],
            'feedback' => ['required', 'string', 'max:3000'],
        ], [
            'score.max' => "The score cannot exceed {$max} points.",
            'feedback.required' => 'Feedback is required when releasing a grade.',
        ]);

        DB::transaction(function () use ($request, $submission, $validated, $max): void {
            $submission->forceFill([
                'score' => round((float) $validated['score'], 2),
                'feedback' => $validated['feedback'],
                'graded_by' => $request->user()->id,
                'graded_at' => now(),
            ])->save();

            // Notify the student that feedback is ready.
            $submission->student->notify(new PortalNotice(
                title: 'Assignment graded — '.$submission->assignment->title,
                body: 'Your submission for '.$submission->assignment->offering->course->code.' has been graded: '.round((float) $validated['score'], 2).'/'.$max.'. Feedback is available on the course page.',
                url: '/courses/'.$submission->assignment->course_offering_id.'/assignments/'.$submission->assignment_id,
            ));
        });

        return back()->with('status', 'Grade released to the student.');
    }
}
