<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\TeamAB;
use App\Enums\TeamSide;
use App\Models\Game;

class GameSideResolver
{
    public function hasRecordedToss(Game $game): bool
    {
        return $game->stateAt()->teamASide !== null;
    }

    public function teamASideForToss(Game $game): TeamSide
    {
        $teamASide = $game->stateAt()->teamASide;

        if ($teamASide === null) {
            return TeamSide::Home;
        }

        return $teamASide;
    }

    public function sideForTeam(Game $game, TeamAB $team): TeamSide
    {
        return $this->sideForTeamFromTeamASide($this->teamASideForToss($game), $team);
    }

    public function sideForTeamFromToss(Game $game, TeamAB $team): ?TeamSide
    {
        $teamASide = $game->stateAt()->teamASide;

        if ($teamASide === null) {
            return null;
        }

        return $this->sideForTeamFromTeamASide($teamASide, $team);
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

    private function sideForTeamFromTeamASide(TeamSide $teamASide, TeamAB $team): TeamSide
    {
        return $team === TeamAB::TeamA
            ? $teamASide
            : $this->oppositeSide($teamASide);
    }
}
