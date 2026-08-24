<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Invoice;
use App\Models\PaymentTransaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Bursary view over invoices and transactions. Manual (bank-transfer)
 * verification lives here — still gated, still audited.
 */
class PaymentsController extends Controller
{
    public function index(): View
    {
        Gate::authorize('manage-payments');

        return view('admin.payments.index', [
            'invoices' => Invoice::query()
                ->with('user')
                ->whereIn('status', ['unpaid', 'paid'])
                ->latest('created_at')
                ->paginate(20),
            'pendingManual' => PaymentTransaction::query()
                ->where('provider', 'manual')
                ->where('status', 'initiated')
                ->with('invoice.user')
                ->get(),
            'totals' => [
                'collected' => (int) Invoice::query()->where('status', 'paid')->sum('amount_due'),
                'outstanding' => (int) Invoice::query()->where('status', 'unpaid')->sum('amount_due'),
            ],
        ]);
    }

    /** A bursary officer confirms a manual bank transfer after checking the teller. */
    public function verifyManual(PaymentTransaction $transaction): RedirectResponse
    {
        Gate::authorize('manage-payments');

        abort_unless($transaction->provider === 'manual' && $transaction->status === 'initiated', 403, 'Only pending manual transfers can be verified here.');

        $transaction->markVerified(auth()->id());

        AuditLog::record('payment.manual_verified', $transaction->invoice, [
            'reference' => $transaction->reference,
            'by' => auth()->user()->email,
        ]);

        return back()->with('status', "Transfer {$transaction->reference} verified — invoice settled.");
    }
}
