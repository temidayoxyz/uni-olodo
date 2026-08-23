<?php

namespace App\Enums;

enum ResultSubmissionStatus: string
{
    case Submitted = 'submitted';
    case Approved = 'approved';
    case Published = 'published';
    case Returned = 'returned';

    public function label(): string
    {
        return match ($this) {
            self::Submitted => 'Awaiting approval',
            self::Approved => 'Approved',
            self::Published => 'Published',
            self::Returned => 'Returned for correction',
        };
    }
}
