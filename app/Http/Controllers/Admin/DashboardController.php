<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\ResultSubmission;
use App\Models\Semester;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $user = auth()->user();
        $semester = Semester::where('is_current', true)->with('session')->first();

        // Operational queues, scoped to what this role actually works with.
        $queues = collect();

        if ($user->hasRole(UserRole::SuperAdmin, UserRole::AdmissionsOfficer, UserRole::Registrar)) {
            $queues['Applications awaiting review'] = Application::query()
                ->whereIn('status', ['submitted'])
                ->count();
        }

        if ($user->hasRole(UserRole::SuperAdmin, UserRole::Registrar)) {
            $queues['Result submissions awaiting approval'] = ResultSubmission::query()
                ->where('status', 'submitted')
                ->count();
        }

        if ($user->isSuperAdmin()) {
            $queues['Staff & students'] = User::query()->whereIn('role', ['student', 'lecturer', 'registrar', 'admissions_officer', 'faculty_admin', 'finance_officer', 'support_staff'])->count();
        }

        $recentApplications = in_array($user->role->value, ['super_admin', 'registrar', 'admissions_officer'])
            ? Application::query()->whereIn('status', ['submitted', 'under_review'])->latest('submitted_at')->take(5)->get(['id', 'number', 'first_name', 'last_name', 'status', 'submitted_at'])
            : collect();

        return view('admin.dashboard', [
            'semester' => $semester,
            'queues' => $queues,
            'recentApplications' => $recentApplications,
        ]);
    }
}
