<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\PaymentTransaction;
use App\Models\User;
use App\Services\Payments\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Shared payments area for students and applicants — both roles hold invoices.
 * Access is ownership-checked per invoice, not by portal.
 */
class PaymentsController extends Controller
{
    public function __construct(private readonly PaymentService $payments) {}

    public function index(Request $request): View
    {
        $invoices = $request->user()->invoices()
            ->with('items')
            ->latest('created_at')
            ->paginate(10);

        return view('payments.index', [
            'invoices' => $invoices,
            'outstanding' => $request->user()->invoices()->where('status', 'unpaid')->sum('amount_due'),
        ]);
    }

    public function show(Request $request, Invoice $invoice): View
    {
        $this->authorizeInvoice($invoice, $request->user());

        return view('payments.show', [
            'invoice' => $invoice->load(['items', 'transactions' => fn ($q) => $q->latest('id')]),
        ]);
    }

    public function pay(Request $request, Invoice $invoice): RedirectResponse
    {
        try {
            $transaction = $this->payments->initialize($invoice, $request->user());
        } catch (ValidationException $e) {
            return back()->with('error', implode(' ', collect($e->errors())->flatten()->all()));
        }

        return redirect('/payments/checkout/'.$transaction->reference);
    }

    /** Simulated hosted checkout (DevGateway). A real provider swaps this page for its own. */
    public function checkout(Request $request, string $reference): View
    {
        $transaction = PaymentTransaction::query()
            ->where('reference', $reference)
            ->with('invoice')
            ->first();

        abort_unless($transaction !== null && $transaction->invoice->user_id === $request->user()->id, 404);

        return view('payments.checkout', [
            'transaction' => $transaction,
        ]);
    }

    public function complete(Request $request, string $reference): RedirectResponse|View
    {
        try {
            $transaction = $this->payments->settle($request->user(), $reference);
        } catch (ValidationException $e) {
            return back()->with('error', implode(' ', collect($e->errors())->flatten()->all()));
        }

        return redirect()->route('payments.show', $transaction->invoice)
            ->with('status', 'Payment confirmed and receipt issued. Thank you.');
    }

    private function authorizeInvoice(Invoice $invoice, User $user): void
    {
        abort_unless($invoice->user_id === $user->id, 403);
    }
}
