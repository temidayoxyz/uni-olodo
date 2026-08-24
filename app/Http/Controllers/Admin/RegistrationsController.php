<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Registration;
use App\Services\RegistrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegistrationsController extends Controller
{
    public function __construct(private readonly RegistrationService $registrations) {}

    public function index(): View
    {
        Gate::authorize('approve-registrations');

        return view('admin.registrations.index', [
            'pending' => Registration::query()
                ->where('status', 'submitted')
                ->with(['student.studentProfile.programme', 'semester', 'items.offering.course'])
                ->oldest('submitted_at')
                ->paginate(15),
        ]);
    }

    public function approve(Registration $registration): RedirectResponse
    {
        Gate::authorize('approve-registrations');

        try {
            $this->registrations->decide($registration, auth()->user(), true);
        } catch (ValidationException $e) {
            return back()->with('error', implode(' ', collect($e->errors())->flatten()->all()));
        }

        return back()->with('status', 'Registration approved — the student has been notified.');
    }

    public function reject(Registration $registration): RedirectResponse
    {
        Gate::authorize('approve-registrations');

        $note = trim((string) request()->input('note'));

        if ($note === '') {
            return back()->with('error', 'Tell the student why the registration was not approved.');
        }

        try {
            $this->registrations->decide($registration, auth()->user(), false, $note);
        } catch (ValidationException $e) {
            return back()->with('error', implode(' ', collect($e->errors())->flatten()->all()));
        }

        return back()->with('status', 'Registration rejected — the student has been notified.');
    }
}
