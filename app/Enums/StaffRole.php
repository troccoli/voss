<?php

declare(strict_types=1);

namespace App\Enums;

enum StaffRole: string
{
    case Coach = 'Coach';
    case AssistantCoach = 'Assistant Coach';
    case Therapist = 'Therapist';
    case Doctor = 'Doctor';
}
