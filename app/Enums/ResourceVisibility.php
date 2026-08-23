<?php

namespace App\Enums;

enum ResourceVisibility: string
{
    case Public = 'public';
    case Students = 'students';
    case Staff = 'staff';

    public function label(): string
    {
        return match ($this) {
            self::Public => 'Public',
            self::Students => 'Students only',
            self::Staff => 'Staff only',
        };
    }
}
