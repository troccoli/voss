<?php

declare(strict_types=1);

namespace App\Enums;

enum TeamSide: string
{
    case Home = 'home';
    case Away = 'away';
}
