<?php

declare(strict_types=1);

namespace App\Enums;

enum GameEventType: string
{
    case TossCompleted = 'toss_completed';
    case LineupSubmitted = 'lineup_submitted';
    case RallyEnded = 'rally_ended';
    case CourtSidesSwapped = 'court_sides_swapped';
    case SubstitutionCompleted = 'substitution_completed';
    case TimeOutRequested = 'time_out_requested';
    case ImproperRequestRecorded = 'improper_request_recorded';
    case SetStarted = 'set_started';
    case SetEnded = 'set_ended';
    case GameEnded = 'game_ended';
}
