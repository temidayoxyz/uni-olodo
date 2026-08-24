<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Assignment;
use App\Models\CourseOffering;
use App\Models\RegistrationItem;
use App\Models\User;

class CourseOfferingPolicy
{
    /**
     * Who can enter the offering's LMS space:
     * enrolled students, the assigned lecturer, and academic administrators.
     */
    public function view(User $user, CourseOffering $offering): bool
    {
        if (in_array($user->role, [UserRole::Registrar, UserRole::FacultyAdmin], true)) {
            return true;
        }

        if ($user->role === UserRole::Lecturer) {
            return $offering->lecturer_id === $user->id;
        }

        if ($user->role === UserRole::Student) {
            return $this->isEnrolled($user, $offering);
        }

        return false;
    }

    /** Manage content, modules, assessments for this offering. */
    public function manage(User $user, CourseOffering $offering): bool
    {
        if ($user->hasRole(UserRole::Registrar)) {
            return true;
        }

        return $user->role === UserRole::Lecturer && $offering->lecturer_id === $user->id;
    }

    /** Grading authority is strictly scoped to the lecturer's own offering. The registrar may inspect. */
    public function grade(User $user, CourseOffering $offering, ?Assignment $assignment = null): bool
    {
        if ($user->hasRole(UserRole::Registrar)) {
            return true;
        }

        if ($user->role !== UserRole::Lecturer || $offering->lecturer_id !== $user->id) {
            return false;
        }

        return $assignment === null || $assignment->course_offering_id === $offering->id;
    }

    public static function isEnrolled(User $user, CourseOffering $offering): bool
    {
        return self::isEnrolledById($user->id, $offering);
    }

    public static function isEnrolledById(int $userId, CourseOffering $offering): bool
    {
        return RegistrationItem::query()
            ->where('course_offering_id', $offering->id)
            ->where('status', 'registered')
            ->whereHas('registration', fn ($q) => $q
                ->where('student_id', $userId)
                ->where('status', 'approved'))
            ->exists();
    }
}
