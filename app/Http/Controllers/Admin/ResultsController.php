<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourseScore;
use App\Models\ResultSubmission;
use App\Services\ResultService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ResultsController extends Controller
{
    public function __construct(private readonly ResultService $results) {}

    /** The registry's results approval queue plus the published archive. */
    public function index(): View
    {
        Gate::authorize('approve-results');

        return view('admin.results.index', [
            'pending' => ResultSubmission::query()
                ->where('status', 'submitted')
                ->with(['offering.course.department', 'submitter'])
                ->oldest('submitted_at')
                ->get(),
            'recent' => ResultSubmission::query()
                ->whereIn('status', ['approved', 'published', 'returned'])
                ->with(['offering.course', 'submitter', 'reviewer'])
                ->latest('submitted_at')
                ->take(10)
                ->get(),
        ]);
    }

    /** Read the provisional gradebook behind a submission before deciding. */
    public function show(ResultSubmission $submission): View
    {
        Gate::authorize('approve-results');

        $offering = $submission->offering()->with('course')->firstOrFail();

        return view('admin.results.show', [
            'submission' => $submission->load(['submitter']),
            'offering' => $offering,
            'rows' => CourseScore::query()
                ->where('course_offering_id', $offering->id)
                ->with('student.studentProfile')
                ->orderBy('student_id')
                ->get()
                ->map(fn ($row) => (object) [
                    'row' => $row,
                    'total' => $row->total(),
                    'letter' => $row->gradeLetter(),
                ]),
        ]);
    }

    public function approve(ResultSubmission $submission): RedirectResponse
    {
        Gate::authorize('approve-results');

        try {
            $this->results->approve($submission, auth()->user());
        } catch (ValidationException $e) {
            return back()->with('error', implode(' ', collect($e->errors())->flatten()->all()));
        }

        return redirect()->route('admin.results.show', $submission)
            ->with('status', 'Results approved. Publishing will write official records students can see.');
    }

    public function returnForCorrections(ResultSubmission $submission): RedirectResponse
    {
        Gate::authorize('approve-results');

        try {
            $this->results->returnForCorrections($submission, auth()->user(), (string) request()->input('note'));
        } catch (ValidationException $e) {
            return back()->with('error', implode(' ', collect($e->errors())->flatten()->all()));
        }

        return redirect()->route('admin.results.index')->with('status', 'Results returned to the lecturer for corrections.');
    }

    public function publish(ResultSubmission $submission): RedirectResponse
    {
        Gate::authorize('approve-results');

        try {
            $this->results->publish($submission, auth()->user());
        } catch (ValidationException $e) {
            return back()->with('error', implode(' ', collect($e->errors())->flatten()->all()));
        }

        return redirect()->route('admin.results.index')
            ->with('status', 'Official results published — students have been notified.');
    }
}
