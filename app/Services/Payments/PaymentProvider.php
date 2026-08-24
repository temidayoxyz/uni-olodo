<?php

namespace App\Services\Payments;

use App\Models\Invoice;
use App\Models\User;

/**
 * Gateway abstraction. A production provider (e.g. Paystack) implements this
 * contract; settlement always flows through server-side verification —
 * a redirect back to the app is never trusted on its own.
 */
interface PaymentProvider
{
    /** Open a transaction with the gateway. Returns its reference and hosted-checkout URL. */
    public function initialize(Invoice $invoice, User $payer): array; // ['reference' => string, 'checkout_url' => string]

    /**
     * Ask the gateway whether a reference was genuinely paid.
     * Only a true return here may settle an invoice.
     */
    public function verify(string $reference): bool;
}
