<?php

namespace App\Http\Controllers\Applicant;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Services\ApplicationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class OfferController extends Controller
{
    public function __construct(private readonly ApplicationService $applications) {}

    public function accept(Request $request): RedirectResponse
    {
        $application = $this->currentApplication($request);

        try {
            $this->applications->acceptOffer($application);
        } catch (ValidationException $e) {
            return back()->with('error', implode(' ', collect($e->errors())->flatten()->all()));
        }

        // The user is now a student; send them to their new home.
        return redirect('/student')
            ->with('status', 'Welcome! Your offer is accepted and your student account is active.');
    }

    public function decline(Request $request): RedirectResponse
    {
        $application = $this->currentApplication($request);

        try {
            $this->applications->declineOffer($application);
        } catch (ValidationException $e) {
            return back()->with('error', implode(' ', collect($e->errors())->flatten()->all()));
        }

        return redirect()->route('applicant.dashboard')->with('status', 'You have declined the offer. Thank you for considering University of Olodo.');
    }

    private function currentApplication(Request $request): Application
    {
        return Application::query()
            ->where('user_id', $request->user()->id)
            ->latest()
            ->first() ?? abort(404);
    }
}
