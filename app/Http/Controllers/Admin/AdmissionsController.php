<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\AuditLog;
use App\Notifications\PortalNotice;
use App\Services\ApplicationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdmissionsController extends Controller
{
    public function __construct(private readonly ApplicationService $applications) {}

    public function index(Request $request): View
    {
        Gate::authorize('review', Application::class);

        $status = $request->query('status');
        $search = trim((string) $request->query('q'));

        $applications = Application::query()
            ->with(['applicant', 'choices.programme'])
            ->when(in_array($status, ['submitted', 'under_review', 'more_info_required', 'accepted', 'rejected', 'waitlisted', 'withdrawn'], true),
                fn ($q) => $q->where('status', $status))
            ->when($search !== '', function ($q) use ($search): void {
                $q->where(fn ($w) => $w
                    ->where('number', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%"));
            })
            ->latest('submitted_at')
            ->paginate(15)
            ->withQueryString();

        $counts = collect([
            'submitted' => 0, 'under_review' => 0, 'more_info_required' => 0,
            'accepted' => 0, 'decided_other' => 0,
        ])->map(fn ($v, $key) => match (true) {
            $key === 'decided_other' => Application::whereIn('status', [ApplicationStatus::Rejected->value, ApplicationStatus::Waitlisted->value, ApplicationStatus::Enrolled->value, ApplicationStatus::Withdrawn->value])->count(),
            default => Application::where('status', $key)->count(),
        });

        return view('admin.admissions.index', [
            'applications' => $applications,
            'status' => $status,
            'search' => $search,
            'counts' => $counts,
        ]);
    }

    public function show(Application $application): View
    {
        Gate::authorize('review', Application::class);

        return view('admin.admissions.show', [
            'application' => $application->load(['applicant', 'choices.programme.department', 'documents.reviewer', 'intakeSession', 'decider']),
        ]);
    }

    /** Officers read applicant uploads; the same private storage boundary applies. */
    public function document(Request $request, ApplicationDocument $document): StreamedResponse
    {
        Gate::authorize('review', $document->application);

        abort_unless(Storage::disk('local')->exists($document->stored_path), 404);

        return Storage::disk('local')->download($document->stored_path, $document->original_name);
    }

    public function verifyDocument(Request $request, ApplicationDocument $document): RedirectResponse
    {
        Gate::authorize('review', $document->application);

        if ($document->verification->value !== 'pending') {
            return back()->with('error', 'This document has already been reviewed.');
        }

        $document->forceFill([
            'verification' => 'verified',
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'reviewer_note' => null,
        ])->save();

        AuditLog::record('application.document_verified', $document->application, ['type' => $document->type]);

        return back()->with('status', 'Document verified.');
    }

    public function rejectDocument(Request $request, ApplicationDocument $document): RedirectResponse
    {
        Gate::authorize('review', $document->application);

        $validated = $request->validate([
            'note' => ['required', 'string', 'max:500'],
        ], ['note.required' => 'Tell the applicant what is wrong with the upload.']);

        if ($document->verification->value !== 'pending') {
            return back()->with('error', 'This document has already been reviewed.');
        }

        $document->forceFill([
            'verification' => 'rejected',
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'reviewer_note' => $validated['note'],
        ])->save();

        AuditLog::record('application.document_rejected', $document->application, ['type' => $document->type, 'note' => $validated['note']]);

        return back()->with('status', 'Document rejected — the applicant will be asked to re-upload.');
    }

    public function startReview(Request $request, Application $application): RedirectResponse
    {
        try {
            $this->applications->review($application, $request->user(), ApplicationStatus::UnderReview);
        } catch (ValidationException $e) {
            return back()->with('error', implode(' ', collect($e->errors())->flatten()->all()));
        }

        return back()->with('status', 'Application moved into review.');
    }

    public function decide(Request $request, Application $application): RedirectResponse
    {
        $target = ApplicationStatus::tryFrom((string) $request->input('decision'));

        if (! in_array($target, [ApplicationStatus::UnderReview, ApplicationStatus::MoreInfoRequired, ApplicationStatus::Accepted, ApplicationStatus::ConditionallyAccepted, ApplicationStatus::Waitlisted, ApplicationStatus::Rejected], true)) {
            return back()->with('error', 'Unknown decision.');
        }

        try {
            $this->applications->review($application, $request->user(), $target, (string) $request->input('note'));
        } catch (ValidationException $e) {
            return back()->with('error', implode(' ', collect($e->errors())->flatten()->all()));
        }

        // Notify the applicant of anything that changes their world.
        if ($target->isDecided() || $target === ApplicationStatus::MoreInfoRequired) {
            $application->applicant->notify(new PortalNotice(
                title: 'Update on your application '.$application->number,
                body: ($target->isDecided() ? "Decision: {$target->label()}. " : 'The admissions office requires additional information. ').trim((string) $application->decision_note),
                url: '/applicant',
            ));
        }

        return redirect()
            ->route('admin.admissions.show', $application)
            ->with('status', 'Application updated: '.$target->label().'.');
    }
}
