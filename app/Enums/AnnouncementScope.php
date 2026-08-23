<?php

namespace App\Enums;

enum AnnouncementScope: string
{
    case University = 'university';
    case Role = 'role';
    case Faculty = 'faculty';
    case Department = 'department';
    case Programme = 'programme';
    case Offering = 'course_offering';

    public function label(): string
    {
        return match ($this) {
            self::University => 'Whole university',
            self::Role => 'Role',
            self::Faculty => 'Faculty',
            self::Department => 'Department',
            self::Programme => 'Programme',
            self::Offering => 'Course offering',
        };
    }
}
