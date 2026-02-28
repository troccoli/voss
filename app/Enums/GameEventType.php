<?php

namespace App\Enums;

enum GameEventType: string
{
    case TossCompleted = 'toss_completed';
    case LineupSubmitted = 'lineup_submitted';
    case RallyWon = 'rally_won';
    case SubstitutionCompleted = 'substitution_completed';
    case TimeOutRequested = 'time_out_requested';
    case SetWon = 'set_won';
}
