<?php

namespace App\Enums;

enum UserRole: string
{
    case SuperAdmin = 'super_admin';
    case Registrar = 'registrar';
    case AdmissionsOfficer = 'admissions_officer';
    case FacultyAdmin = 'faculty_admin';
    case Lecturer = 'lecturer';
    case FinanceOfficer = 'finance_officer';
    case SupportStaff = 'support_staff';
    case Student = 'student';
    case Applicant = 'applicant';

    /** Roles with an authenticated portal home of their own. */
    public function portalPrefix(): ?string
    {
        return match ($this) {
            self::Applicant => 'applicant',
            self::Student => 'student',
            self::Lecturer => 'lecturer',
            self::SuperAdmin, self::Registrar, self::AdmissionsOfficer,
            self::FacultyAdmin, self::FinanceOfficer, self::SupportStaff => 'admin',
            default => null,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Administrator',
            self::Registrar => 'Registrar',
            self::AdmissionsOfficer => 'Admissions Officer',
            self::FacultyAdmin => 'Faculty Administrator',
            self::Lecturer => 'Lecturer',
            self::FinanceOfficer => 'Finance Officer',
            self::SupportStaff => 'Support Staff',
            self::Student => 'Student',
            self::Applicant => 'Applicant',
        };
    }

    public function isStaff(): bool
    {
        return match ($this) {
            self::Student, self::Applicant => false,
            default => true,
        };
    }
}
