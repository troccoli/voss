<?php

declare(strict_types=1);

namespace App\Enums;

enum TeamAB: string
{
    case TeamA = 'team_a';
    case TeamB = 'team_b';

    public function label(): string
    {
        return $this === self::TeamA
            ? 'Team A'
            : 'Team B';
    }
}
