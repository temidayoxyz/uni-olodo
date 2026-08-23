<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

#[Fillable([
    'invoice_id', 'reference', 'provider', 'provider_reference', 'amount', 'currency',
    'status', 'verified_at', 'verified_by', 'metadata',
])]
class PaymentTransaction extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'metadata' => 'array',
            'verified_at' => 'datetime',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /** Only a server-side verification marks success — never the redirect back. */
    public function markVerified(int|string $verifierId): void
    {
        if ($this->status === 'verified') {
            return;
        }

        DB::transaction(function () use ($verifierId): void {
            $this->forceFill([
                'status' => 'verified',
                'verified_at' => now(),
                'verified_by' => $verifierId,
            ])->save();

            $invoice = $this->invoice()->lockForUpdate()->first();

            if ($invoice !== null && $invoice->status !== 'paid') {
                $invoice->forceFill(['status' => 'paid', 'paid_at' => now()])->save();
            }
        });
    }
}
