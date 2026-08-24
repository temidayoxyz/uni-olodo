<?php

namespace App\Http\Controllers\Lms;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\CourseOffering;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AssignmentController extends Controller
{
    public function index(CourseOffering $offering): View
    {
        Gate::authorize('view', $offering);

        $user = auth()->user();

        $assignments = Assignment::query()
            ->where('course_offering_id', $offering->id)
            ->whereNotNull('published_at')
            ->when($user->cannot('manage', $offering),
                fn ($q) => $q->with(['submissions' => fn ($s) => $s->where('student_id', $user->id)]))
            ->when($user->can('manage', $offering),
                fn ($q) => $q->withCount(['submissions', 'submissions as pending_count' => fn ($s) => $s->whereNull('graded_at')]))
            ->orderBy('due_at')
            ->get();

        return view('lms.assignments-index', [
            'offering' => $offering->load('course'),
            'assignments' => $assignments,
            'managing' => $user->can('manage', $offering),
        ]);
    }

    public function show(CourseOffering $offering, Assignment $assignment): View
    {
        Gate::authorize('view', $offering);
        abort_unless($assignment->course_offering_id === $offering->id, 404);

        $user = auth()->user();
        $managing = $user->can('manage', $offering);

        $submission = null;
        if (! $managing) {
            $submission = $assignment->submissions()->where('student_id', $user->id)->first();
        }

        return view('lms.assignment-show', [
            'offering' => $offering->load('course'),
            'assignment' => $assignment,
            'submission' => $submission,
            'managing' => $managing,
            'canSubmit' => ! $managing
                && $assignment->published_at !== null
                && $assignment->acceptsSubmissions()
                && ($submission === null || $submission->graded_at === null),
        ]);
    }

    /** Students submit or replace (replacement only while ungraded and inside the window). */
    public function submit(Request $request, CourseOffering $offering, Assignment $assignment): RedirectResponse
    {
        abort_if($request->user()->can('manage', $offering), 403);
        Gate::authorize('view', $offering);

        abort_unless($assignment->course_offering_id === $offering->id, 404);

        $existing = $assignment->submissions()->where('student_id', $request->user()->id)->first();

        // A graded submission is closed — feedback has been released.
        abort_if($existing?->isGraded(), 403, 'This assignment has already been graded.');
        abort_unless($assignment->acceptsSubmissions(), 403, 'The submission window for this assignment is closed.');

        $validated = $request->validate([
            'file' => ['required', 'file', 'max:'.(10 * 1024), 'mimes:pdf,zip,doc,docx,txt'],
            'note' => ['nullable', 'string', 'max:1000'],
        ], [
            'file.mimes' => 'Upload a PDF, Word document, ZIP archive, or text file.',
            'file.max' => 'Files must be 10 MB or smaller.',
        ]);

        $file = $request->file('file');

        if ($existing !== null && Storage::disk('local')->exists($existing->file_path)) {
            Storage::disk('local')->delete($existing->file_path);
        }

        $path = $file->store("assignments/{$assignment->id}/{$request->user()->id}", 'local');

        // Replace-in-place keeps one row per student (unique constraint).
        if ($existing !== null) {
            $existing->forceFill([
                'file_path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size_bytes' => $file->getSize(),
                'note' => $validated['note'] ?? null,
                'submitted_at' => now(),
            ])->save();
        } else {
            $assignment->submissions()->create([
                'student_id' => $request->user()->id,
                'file_path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size_bytes' => $file->getSize(),
                'note' => $validated['note'] ?? null,
                'submitted_at' => now(),
            ]);
        }

        $late = $assignment->isPastDue() ? ' (recorded as late)' : '';

        return back()->with('status', 'Submission received'.$late.'. You may replace it until the deadline closes.');
    }

    public function downloadSubmission(Request $request, CourseOffering $offering, AssignmentSubmission $submission): StreamedResponse
    {
        $user = $request->user();

        // Own submission, or the offering's grader.
        if ($submission->student_id !== $user->id) {
            Gate::authorize('grade', [$offering, $submission->assignment]);
        }

        abort_unless($submission->assignment->course_offering_id === $offering->id, 404);
        abort_unless(Storage::disk('local')->exists($submission->file_path), 404);

        return Storage::disk('local')->download($submission->file_path, $submission->original_name);
    }
}
