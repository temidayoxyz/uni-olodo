<?php

namespace App\Support;

use App\Enums\UserRole;
use App\Models\User;

/**
 * Portal navigation per role area. Rendered by the portal shell;
 * never a security boundary — routes and policies enforce access server-side.
 */
final class Navigation
{
    /**
     * @return array<int, array{section?: string, items: array<int, array{label: string, url: string, icon: string}>}>
     */
    public static function for(User $user): array
    {
        return match ($user->role) {
            UserRole::Applicant => self::applicant(),
            UserRole::Student => self::student(),
            UserRole::Lecturer => self::lecturer(),
            default => self::admin($user),
        };
    }

    private static function applicant(): array
    {
        return [
            ['items' => [
                ['label' => 'Overview', 'url' => '/applicant', 'icon' => 'layout-dashboard'],
                ['label' => 'My Application', 'url' => '/applicant/application', 'icon' => 'file-text'],
            ]],
            ['items' => [
                ['label' => 'Payments', 'url' => '/applicant/payments', 'icon' => 'receipt'],
                ['label' => 'Support', 'url' => '/support', 'icon' => 'life-buoy'],
            ]],
        ];
    }

    private static function student(): array
    {
        return [
            ['items' => [
                ['label' => 'Dashboard', 'url' => '/student', 'icon' => 'layout-dashboard'],
                ['label' => 'My Academics', 'url' => '/student/academics', 'icon' => 'graduation-cap'],
                ['label' => 'Courses', 'url' => '/student/courses', 'icon' => 'book-open'],
                ['label' => 'Registration', 'url' => '/student/registration', 'icon' => 'clipboard-check'],
                ['label' => 'Timetable', 'url' => '/student/timetable', 'icon' => 'calendar-days'],
                ['label' => 'Results', 'url' => '/student/results', 'icon' => 'award'],
            ]],
            ['items' => [
                ['label' => 'Resources', 'url' => '/resources', 'icon' => 'library'],
                ['label' => 'Payments', 'url' => '/student/payments', 'icon' => 'receipt'],
                ['label' => 'Support', 'url' => '/support', 'icon' => 'life-buoy'],
            ]],
        ];
    }

    private static function lecturer(): array
    {
        return [
            ['items' => [
                ['label' => 'Dashboard', 'url' => '/lecturer', 'icon' => 'layout-dashboard'],
                ['label' => 'My Courses', 'url' => '/lecturer/courses', 'icon' => 'book-open'],
                ['label' => 'Teaching Schedule', 'url' => '/lecturer/schedule', 'icon' => 'calendar-days'],
                ['label' => 'Grading', 'url' => '/lecturer/grading', 'icon' => 'check-square'],
            ]],
            ['items' => [
                ['label' => 'Resources', 'url' => '/resources', 'icon' => 'library'],
                ['label' => 'Support', 'url' => '/support', 'icon' => 'life-buoy'],
            ]],
        ];
    }

    private static function admin(User $user): array
    {
        $sections = [
            ['items' => [
                ['label' => 'Dashboard', 'url' => '/admin', 'icon' => 'layout-dashboard'],
            ]],
            ['section' => 'Academic operations', 'items' => [
                ['label' => 'Academic Structure', 'url' => '/admin/academics', 'icon' => 'building-2'],
                ['label' => 'Admissions', 'url' => '/admin/admissions', 'icon' => 'inbox'],
                ['label' => 'Results', 'url' => '/admin/results', 'icon' => 'award'],
            ]],
            ['section' => 'University content', 'items' => [
                ['label' => 'Users & Roles', 'url' => '/admin/users', 'icon' => 'users'],
                ['label' => 'Announcements', 'url' => '/admin/announcements', 'icon' => 'megaphone'],
                ['label' => 'News & Events', 'url' => '/admin/news', 'icon' => 'newspaper'],
                ['label' => 'Resources', 'url' => '/admin/resources', 'icon' => 'library'],
            ]],
            ['section' => 'Oversight', 'items' => [
                ['label' => 'Audit Log', 'url' => '/admin/audit', 'icon' => 'scroll-text'],
            ]],
        ];

        // Scope what narrower roles see — the server still enforces every route.
        if ($user->role === UserRole::AdmissionsOfficer) {
            return [
                $sections[0],
                $sections[1],
                ['section' => 'University content', 'items' => [$sections[2]['items'][3]]],
            ];
        }

        if ($user->role === UserRole::FinanceOfficer) {
            return [
                $sections[0],
                ['section' => 'Finance', 'items' => [
                    ['label' => 'Payments', 'url' => '/admin/payments', 'icon' => 'receipt'],
                ]],
            ];
        }

        return $sections;
    }
}
