<?php

namespace App\Enums;

enum InvoiceType: string
{
    case ApplicationFee = 'application_fee';
    case Tuition = 'tuition';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::ApplicationFee => 'Application fee',
            self::Tuition => 'Tuition',
            self::Other => 'Other charges',
        };
    }
}
