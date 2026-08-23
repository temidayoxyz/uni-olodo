<?php

namespace App\Http\Controllers\Applicant;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\Programme;
use App\Services\ApplicationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * The application wizard. Steps are freely navigable; each save validates only
 * its own section, and submission re-validates the whole via ApplicationService.
 */
class ApplicationWizardController extends Controller
{
    private const STEPS = ['personal', 'education', 'choices', 'documents', 'review'];

    public function __construct(private readonly ApplicationService $applications) {}

    /** Wizard hub: shows current step, or the post-submission state page. */
    public function show(Request $request): View
    {
        $application = $this->currentApplication($request);

        if ($application === null) {
            return view('applicant.wizard-start');
        }

        return view('applicant.wizard', [
            'application' => $application->load(['choices.programme.department', 'documents']),
            'step' => in_array($request->query('step'), self::STEPS, true) ? $request->query('step') : 'personal',
            'programmes' => Programme::query()->where('is_active', true)->with('department')->orderBy('name')->get(),
            'blockers' => $this->applications->submissionBlockers($application),
            'documentTypes' => ApplicationDocument::TYPES,
        ]);
    }

    public function start(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($this->currentApplication($request) !== null) {
            return redirect()->route('applicant.application');
        }

        // An applicant holds at most one live application at a time.
        $application = Application::create([
            'user_id' => $user->id,
            'number' => $this->nextApplicationNumber(),
            'intake_session_id' => AcademicSession::current()?->id,
            'first_name' => collect(explode(' ', $user->name))->first() ?? '',
            'last_name' => trim(collect(explode(' ', $user->name))->slice(1)->implode(' ')) ?? '',
            'date_of_birth' => now()->subYears(18),
            'gender' => '',
            'phone' => '',
            'address' => '',
            'qualification' => '',
            'examination_year' => (int) date('Y'),
            'status' => ApplicationStatus::Draft,
        ]);

        return redirect()->route('applicant.application', ['step' => 'personal']);
    }

    public function savePersonal(Request $request): RedirectResponse
    {
        $application = $this->currentApplicationOrFail($request);

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'other_names' => ['nullable', 'string', 'max:128'],
            'date_of_birth' => ['required', 'date', 'before:today', 'after:'.now()->subYears(40)->toDateString()],
            'gender' => ['required', 'in:male,female'],
            'phone' => ['required', 'string', 'regex:/^0[0-9]{10}$/', 'max:15'],
            'address' => ['required', 'string', 'max:500'],
            'state_of_origin' => ['required', 'string', 'max:64'],
        ], [
            'phone.regex' => 'Enter a valid Nigerian phone number, e.g. 08031234567.',
            'date_of_birth.before' => 'Your date of birth must be in the past.',
            'date_of_birth.after' => 'Please check your date of birth.',
        ]);

        $application->update($validated);

        return redirect()->route('applicant.application', ['step' => 'education'])
            ->with('status', 'Personal details saved.');
    }

    public function saveEducation(Request $request): RedirectResponse
    {
        $application = $this->currentApplicationOrFail($request);

        $validated = $request->validate([
            'qualification' => ['required', 'in:waec,neco,nabteb,equivalent'],
            'examination_year' => ['required', 'integer', 'min:2000', 'max:'.date('Y')],
            'previous_school' => ['nullable', 'string', 'max:191'],
            'personal_statement' => ['nullable', 'string', 'max:3000'],
        ]);

        $application->update($validated);

        return redirect()->route('applicant.application', ['step' => 'choices'])
            ->with('status', 'Educational background saved.');
    }

    public function saveChoices(Request $request): RedirectResponse
    {
        $application = $this->currentApplicationOrFail($request);

        $validated = $request->validate([
            'choice_1' => ['required', 'exists:programmes,id'],
            'choice_2' => ['nullable', 'exists:programmes,id', 'different:choice_1'],
        ], [
            'choice_2.different' => 'Your second choice must be a different programme.',
        ]);

        DB::transaction(function () use ($application, $validated): void {
            $application->choices()->delete();

            foreach ([1 => $validated['choice_1'], 2 => $validated['choice_2'] ?? null] as $rank => $programmeId) {
                if ($programmeId !== null) {
                    $application->choices()->create(['programme_id' => $programmeId, 'rank' => $rank]);
                }
            }
        });

        return redirect()->route('applicant.application', ['step' => 'documents'])
            ->with('status', 'Programme choices saved.');
    }

    public function submit(Request $request): RedirectResponse
    {
        $application = $this->currentApplicationOrFail($request);

        try {
            $this->applications->submit($application);
        } catch (ValidationException $e) {
            return back()->with('error', implode(' ', $e->errors()['application'] ?? ['Submission failed.']));
        }

        return redirect()->route('applicant.dashboard')
            ->with('status', 'Your application has been submitted. You will be notified as it moves through review.');
    }

    public function withdraw(Request $request): RedirectResponse
    {
        $application = $this->currentApplicationOrFail($request);

        Gate::authorize('withdraw', $application);

        try {
            $this->applications->withdraw($application);
        } catch (ValidationException $e) {
            return back()->with('error', implode(' ', $e->errors()['status'] ?? ['The application cannot be withdrawn at this stage.']));
        }

        return redirect()->route('applicant.dashboard')
            ->with('status', 'Your application has been withdrawn.');
    }

    // --- helpers -------------------------------------------------------------

    private function currentApplication(Request $request): ?Application
    {
        return Application::query()->where('user_id', $request->user()->id)->latest()->first();
    }

    private function currentApplicationOrFail(Request $request): Application
    {
        $application = $this->currentApplication($request) ?? abort(404);

        // Route URLs carry no application id, so the policy is enforced here:
        // decided/withdrawn applications are closed to the applicant.
        if (! $application->statusIs(ApplicationStatus::Draft, ApplicationStatus::MoreInfoRequired)) {
            Gate::authorize('update', $application);
        }

        return $application;
    }

    private function nextApplicationNumber(): string
    {
        do {
            $number = sprintf('UOA-%s-%06d', date('Y'), random_int(1, 999999));
        } while (Application::where('number', $number)->exists());

        return $number;
    }
}
