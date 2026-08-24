<?php

namespace App\Services\Payments;

use App\Models\AuditLog;
use App\Models\Invoice;
use App\Models\PaymentTransaction;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    public function __construct(private readonly PaymentProvider $gateway) {}

    /** Open a payment attempt on an unpaid invoice owned by the payer. */
    public function initialize(Invoice $invoice, User $payer): PaymentTransaction
    {
        if ($invoice->user_id !== $payer->id) {
            abort(403);
        }

        if ($invoice->isPaid()) {
            throw ValidationException::withMessages(['payment' => 'This invoice is already settled.']);
        }

        if ($invoice->status === 'void') {
            throw ValidationException::withMessages(['payment' => 'This invoice has been voided by the bursary.']);
        }

        // One open attempt at a time per invoice: resume it instead of stacking.
        $open = $invoice->transactions()->where('status', 'initiated')->latest('id')->first();

        if ($open !== null) {
            return $open;
        }

        ['reference' => $reference] = $this->gateway->initialize($invoice, $payer);

        return PaymentTransaction::create([
            'invoice_id' => $invoice->id,
            'reference' => $reference,
            'provider' => 'dev',
            'amount' => $invoice->amount_due,
            'currency' => 'NGN',
            'status' => 'initiated',
        ]);
    }

    /**
     * Verify a reference with the gateway and, only on confirmation, settle the
     * transaction and its invoice atomically. Idempotent.
     */
    public function settle(User $payer, string $reference): PaymentTransaction
    {
        $transaction = PaymentTransaction::query()
            ->where('reference', $reference)
            ->with('invoice')
            ->firstOrFail();

        if ($transaction->invoice->user_id !== $payer->id) {
            abort(403);
        }

        if ($transaction->status === 'verified') {
            return $transaction; // idempotent
        }

        if ($transaction->status !== 'initiated') {
            throw ValidationException::withMessages([
                'payment' => 'This payment attempt is no longer pending (status: '.$transaction->status.').',
            ]);
        }

        if (! $this->gateway->verify($reference)) {
            $transaction->forceFill(['status' => 'failed'])->save();

            throw ValidationException::withMessages(['payment' => 'The gateway could not confirm this payment.']);
        }

        if ((int) $transaction->amount !== (int) $transaction->invoice->amount_due) {
            throw ValidationException::withMessages(['payment' => 'Amount mismatch against the invoice — payment refused.']);
        }

        $transaction->markVerified($payer->id);

        AuditLog::record('payment.settled', $transaction->invoice, [
            'reference' => $transaction->reference,
            'provider' => $transaction->provider,
            'amount' => $transaction->amount,
        ]);

        return $transaction->fresh();
    }
}
