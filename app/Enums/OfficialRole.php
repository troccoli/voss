<?php

declare(strict_types=1);

namespace App\Enums;

enum OfficialRole: string
{
    case FirstReferee = '1st Referee';
    case SecondReferee = '2nd Referee';
    case Scorer = 'Scorer';
    case AssistantScorer = 'Assistant Scorer';
    case LineJudge1 = 'Line Judge 1';
    case LineJudge2 = 'Line Judge 2';
    case LineJudge3 = 'Line Judge 3';
    case LineJudge4 = 'Line Judge 4';
}
