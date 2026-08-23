<?php

namespace App\Policies;

use App\Enums\ApplicationStatus;
use App\Enums\UserRole;
use App\Models\Application;
use App\Models\User;

class ApplicationPolicy
{
    public function view(User $user, Application $application): bool
    {
        return $application->user_id === $user->id
            || in_array($user->role, [UserRole::AdmissionsOfficer, UserRole::Registrar], true);
    }

    /** Only the owning applicant edits a draft (or re-enters after an info request). */
    public function update(User $user, Application $application): bool
    {
        return $application->user_id === $user->id
            && $application->statusIs(
                ApplicationStatus::Draft,
                ApplicationStatus::MoreInfoRequired,
            );
    }

    public function submit(User $user, Application $application): bool
    {
        return $this->update($user, $application);
    }

    public function withdraw(User $user, Application $application): bool
    {
        return $application->user_id === $user->id
            && $application->statusIs(
                ApplicationStatus::Submitted,
                ApplicationStatus::UnderReview,
                ApplicationStatus::MoreInfoRequired,
            );
    }

    public function review(User $user): bool
    {
        return in_array($user->role, [UserRole::AdmissionsOfficer, UserRole::Registrar], true);
    }
}
