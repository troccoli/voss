<?php

declare(strict_types=1);

namespace App\Services\GameState;

class SetScoringRules
{
    private const int REGULAR_SET_TARGET_POINTS = 25;

    private const int DECIDING_SET_TARGET_POINTS = 15;

    private const int DECIDING_SET_NUMBER = 5;

    private const int MINIMUM_WIN_MARGIN = 2;

    public function targetPoints(int $setNumber): int
    {
        return $setNumber === self::DECIDING_SET_NUMBER
            ? self::DECIDING_SET_TARGET_POINTS
            : self::REGULAR_SET_TARGET_POINTS;
    }

    public function canEndSet(int $setNumber, int $scoreTeamA, int $scoreTeamB): bool
    {
        $highestScore = max($scoreTeamA, $scoreTeamB);
        $scoreDifference = abs($scoreTeamA - $scoreTeamB);

        return $highestScore >= $this->targetPoints($setNumber)
            && $scoreDifference >= self::MINIMUM_WIN_MARGIN;
    }
}
