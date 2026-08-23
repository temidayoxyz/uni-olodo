<?php

namespace App\Support;

use App\Models\User;

final class PortalUrl
{
    /** The portal home URL for a user's role — single source of truth for post-login redirect. */
    public static function homeFor(User $user): string
    {
        return match ($user->role?->portalPrefix()) {
            'applicant' => '/applicant',
            'student' => '/student',
            'lecturer' => '/lecturer',
            'admin' => '/admin',
            default => route('home'),
        };
    }
}
