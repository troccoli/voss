<?php

declare(strict_types=1);

namespace App\Enums;

enum MisconductSanction: string
{
    case Warning = 'warning';
    case Penalty = 'penalty';
    case Expulsion = 'expulsion';
    case Disqualification = 'disqualification';
}
