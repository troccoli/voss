<?php

declare(strict_types=1);

namespace App\Enums;

enum MisconductSubjectType: string
{
    case Player = 'player';
    case Staff = 'staff';
}
