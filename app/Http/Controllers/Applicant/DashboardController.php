<?php

namespace App\Http\Controllers\Applicant;

use App\Enums\InvoiceType;
use App\Http\Controllers\Controller;
use App\Models\Application;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $user = auth()->user();

        $application = Application::query()
            ->where('user_id', $user->id)
            ->with(['choices.programme.department', 'documents'])
            ->latest()
            ->first();

        return view('applicant.dashboard', [
            'application' => $application,
            'invoice' => $user->invoices()->where('type', InvoiceType::ApplicationFee->value)->first(),
        ]);
    }
}
