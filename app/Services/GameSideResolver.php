<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\TeamAB;
use App\Enums\TeamSide;
use App\Events\Payloads\TossCompletedPayload;
use App\Models\Game;

class GameSideResolver
{
    public function __construct(
        protected CacheRepository $cacheRepository
    ) {}

    public function hasRecordedToss(Game $game): bool
    {
        return $this->tossPayload($game) !== null;
    }

    public function teamASideForToss(Game $game): TeamSide
    {
        $tossPayload = $this->tossPayload($game);

        if ($tossPayload === null) {
            return TeamSide::Home;
        }

        return $tossPayload->teamA;
    }

    public function sideForTeam(Game $game, TeamAB $team): TeamSide
    {
        return $this->sideForTeamFromTeamASide($this->teamASideForToss($game), $team);
    }

    public function sideForTeamFromToss(Game $game, TeamAB $team): ?TeamSide
    {
        $tossPayload = $this->tossPayload($game);

        if ($tossPayload === null) {
            return null;
        }

        return $this->sideForTeamFromTeamASide($tossPayload->teamA, $team);
    }

    public function teamOnLeft(int $completedSets): TeamAB
    {
        return $completedSets % 2 === 0
            ? TeamAB::TeamA
            : TeamAB::TeamB;
    }

    public function teamOnRight(int $completedSets): TeamAB
    {
        return $this->oppositeTeam($this->teamOnLeft($completedSets));
    }

    public function oppositeTeam(TeamAB $team): TeamAB
    {
        return $team === TeamAB::TeamA
            ? TeamAB::TeamB
            : TeamAB::TeamA;
    }

    public function oppositeSide(TeamSide $side): TeamSide
    {
        return $side === TeamSide::Home
            ? TeamSide::Away
            : TeamSide::Home;
    }

    private function tossPayload(Game $game): ?TossCompletedPayload
    {
        return $this->cacheRepository->latestTossPayload($game);
    }

    private function sideForTeamFromTeamASide(TeamSide $teamASide, TeamAB $team): TeamSide
    {
        return $team === TeamAB::TeamA
            ? $teamASide
            : $this->oppositeSide($teamASide);
    }
}
