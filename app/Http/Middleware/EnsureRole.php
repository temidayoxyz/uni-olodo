<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    /**
     * Restrict a route to one or more roles. Usage: ->middleware('role:registrar,super_admin')
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if ($user === null) {
            return redirect()->guest(route('login'));
        }

        $allowed = array_map(
            fn (string $role) => UserRole::tryFrom(trim($role))?->value,
            $roles,
        );

        if (! in_array($user->role?->value, $allowed, true)) {
            abort(403);
        }

        return $next($request);
    }
}
