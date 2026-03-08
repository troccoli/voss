<?php

declare(strict_types=1);

namespace App\Enums;

enum GameEventType: string
{
    case TossCompleted = 'toss_completed';
    case LineupSubmitted = 'lineup_submitted';
    case RallyEnded = 'rally_ended';
    case SubstitutionCompleted = 'substitution_completed';
    case TimeOutRequested = 'time_out_requested';
    case SetStarted = 'set_started';
    case SetEnded = 'set_ended';
    case GameEnded = 'game_ended';
}
