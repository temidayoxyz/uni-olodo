<?php

namespace App\Services\Payments;

use App\Models\Invoice;
use App\Models\User;

/**
 * Development/demo gateway: simulates a hosted checkout page inside the app.
 * Clearly labelled as simulated everywhere it appears. Settlement still runs
 * through the same verification path a real provider would use.
 */
class DevGateway implements PaymentProvider
{
    public function initialize(Invoice $invoice, User $payer): array
    {
        $reference = 'UOPAY-DEV-'.strtoupper(bin2hex(random_bytes(6)));

        return [
            'reference' => $reference,
            'checkout_url' => '/payments/checkout/'.$reference,
        ];
    }

    /** The dev gateway confirms any transaction that exists and was initiated. */
    public function verify(string $reference): bool
    {
        return filled($reference);
    }
}
