<?php

namespace App\Enums;

enum GameEventType: string
{
    case TossCompleted = 'toss_completed';
    case LineupSubmitted = 'lineup_submitted';
}
