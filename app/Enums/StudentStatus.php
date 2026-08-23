<?php

namespace App\Enums;

enum StudentStatus: string
{
    case Active = 'active';
    case Probation = 'probation';
    case Suspended = 'suspended';
    case Graduated = 'graduated';
    case Withdrawn = 'withdrawn';
}
