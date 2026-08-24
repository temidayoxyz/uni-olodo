<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Users & roles — super administrator territory. Role changes are the most
 * sensitive action in the system and are always audited.
 */
class UsersController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('manage-users');

        $role = $request->query('role');
        $search = trim((string) $request->query('q'));

        $users = User::query()
            ->when(in_array($role, array_column(UserRole::cases(), 'value'), true),
                fn ($q) => $q->where('role', $role))
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'roles' => UserRole::cases(),
            'activeRole' => $role,
            'search' => $search,
            'roleCounts' => collect(UserRole::cases())
                ->mapWithKeys(fn (UserRole $r) => [$r->value => User::where('role', $r->value)->count()]),
        ]);
    }

    public function edit(User $user): View
    {
        Gate::authorize('manage-users');

        return view('admin.users.edit', [
            'user' => $user->loadCount(['registrations']),
            'roles' => UserRole::cases(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('manage-users');

        $validated = $request->validate([
            'role' => ['required', 'in:'.implode(',', array_column(UserRole::cases(), 'value'))],
            'status' => ['required', 'in:active,suspended'],
        ]);

        // A super admin cannot demote or suspend themselves — avoid lock-out.
        if ($user->id === $request->user()->id && ($validated['role'] !== UserRole::SuperAdmin->value || $validated['status'] !== 'active')) {
            return back()->with('error', 'You cannot demote or suspend your own account.');
        }

        $previousRole = $user->role;

        $user->forceFill([
            'role' => $validated['role'],
            'status' => $validated['status'],
        ])->save();

        AuditLog::record('user.updated', $user, [
            'by' => $request->user()->email,
            'role' => ['from' => $previousRole?->value, 'to' => $validated['role']],
            'status' => ['to' => $validated['status']],
        ]);

        return redirect()->route('admin.users.index')
            ->with('status', "{$user->name} updated — now {$user->role->label()}, {$validated['status']}.");
    }
}
