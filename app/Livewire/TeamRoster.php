<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Data\GameState\GameState;
use App\Enums\StaffRole;
use App\Enums\TeamAB;
use App\Enums\TeamSide;
use App\Models\Game;
use App\Services\GameSideResolver;
use App\Services\ScoresheetDataRepository;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Reactive;
use Livewire\Component;

class TeamRoster extends Component
{
    #[Reactive]
    #[Locked]
    public int $gameId;

    #[Reactive]
    public TeamAB $team = TeamAB::TeamA;

    #[Reactive]
    public bool $leftSide = true;

    #[Reactive]
    public ?GameState $gameState = null;

    public function mount(
        ?int $gameId = null,
        TeamAB $team = TeamAB::TeamA,
        bool $leftSide = true,
    ): void {
        abort_if(is_null($gameId), 404);

        $this->gameId = $gameId;
        $this->team = $team;
        $this->leftSide = $leftSide;
    }

    public function render(): View
    {
        $activeGame = $this->activeGame();
        $teamPlayers = $activeGame === null ? [] : $this->playersForTeam($activeGame, $this->team);
        $rosterPlayerCount = count($teamPlayers);
        $players = $this->benchPlayers($teamPlayers);
        $teamStaff = $activeGame === null ? [] : $this->staffForTeam($activeGame, $this->team);
        $lineupSubmitted = $this->hasLineupBeenSubmitted($this->team);
        $placeholderCount = $lineupSubmitted ? 0 : max(0, $rosterPlayerCount - 6);

        return view('livewire.team-roster', [
            'players' => $players,
            'showPlayerPlaceholders' => $placeholderCount > 0,
            'placeholderCount' => $placeholderCount,
            'hasRosterPlayers' => $rosterPlayerCount > 0,
            'staffMarkers' => $this->buildStaffMarkers($teamStaff),
            'reverseLayout' => $this->leftSide,
            'keyPrefix' => $this->leftSide ? 'left-player' : 'right-player',
            'markerTone' => $this->team === TeamAB::TeamA ? 'bg-blue-600' : 'bg-red-600',
            'timeoutsTaken' => $this->timeoutsTaken(),
            'substitutionsTaken' => $this->substitutionsTaken(),
        ]);
    }

    /**
     * @return array<int, array{player_key: int, number: int, last_name: string}>
     */
    private function playersForTeam(Game $game, TeamAB $team): array
    {
        return $this->scoresheetDataRepository()->playersForSide($game, $this->targetSideForTeam($game, $team));
    }

    /**
     * @param  array<int, array{player_key: int, number: int, last_name: string}>  $players
     * @return array<int, array{player_key: int, number: int, last_name: string}>
     */
    private function benchPlayers(array $players): array
    {
        $onCourtNumbers = $this->onCourtRosterNumbers($this->team);

        if ($onCourtNumbers === []) {
            return [];
        }

        return array_values(array_filter($players, fn (array $player): bool => ! in_array($player['number'], $onCourtNumbers, true)));
    }

    /**
     * @return array<int, array{staff_key: int, role: StaffRole}>
     */
    private function staffForTeam(Game $game, TeamAB $team): array
    {
        return $this->scoresheetDataRepository()->staffForSide($game, $this->targetSideForTeam($game, $team));
    }

    /**
     * @param  array<int, array{staff_key: int, role: StaffRole}>  $staff
     * @return array<int, array{role_letter: string, subscript: int|null}>
     */
    private function buildStaffMarkers(array $staff): array
    {
        if ($staff === []) {
            return [];
        }

        $assistantCoaches = 0;
        $hasCoach = false;
        $hasDoctor = false;
        $hasTherapist = false;

        foreach ($staff as $staffMember) {
            match ($staffMember['role']) {
                StaffRole::Coach => $hasCoach = true,
                StaffRole::AssistantCoach => $assistantCoaches++,
                StaffRole::Doctor => $hasDoctor = true,
                StaffRole::Therapist => $hasTherapist = true,
            };
        }

        $markers = [];

        if ($hasCoach) {
            $markers[] = ['role_letter' => 'C', 'subscript' => null];
        }

        if ($assistantCoaches >= 1) {
            $markers[] = ['role_letter' => 'A', 'subscript' => 1];
        }

        if ($assistantCoaches >= 2) {
            $markers[] = ['role_letter' => 'A', 'subscript' => 2];
        }

        if ($hasDoctor) {
            $markers[] = ['role_letter' => 'D', 'subscript' => null];
        }

        if ($hasTherapist) {
            $markers[] = ['role_letter' => 'T', 'subscript' => null];
        }

        return $markers;
    }

    private function scoresheetDataRepository(): ScoresheetDataRepository
    {
        return app(ScoresheetDataRepository::class);
    }

    private function targetSideForTeam(Game $game, TeamAB $team): TeamSide
    {
        return $this->gameSideResolver()->sideForTeam($game, $team);
    }

    /**
     * @return array<int, int>
     */
    private function onCourtRosterNumbers(TeamAB $team): array
    {
        $state = $this->gameState ?? GameState::initial();

        return $team === TeamAB::TeamA
            ? array_values($state->rotationTeamA)
            : array_values($state->rotationTeamB);
    }

    private function hasLineupBeenSubmitted(TeamAB $team): bool
    {
        return $this->onCourtRosterNumbers($team) !== [];
    }

    private function activeGame(): ?Game
    {
        return Game::query()->find($this->gameId);
    }

    private function timeoutsTaken(): int
    {
        $state = $this->gameState ?? GameState::initial();

        return $this->team === TeamAB::TeamA ? $state->timeoutsTeamA : $state->timeoutsTeamB;
    }

    private function substitutionsTaken(): int
    {
        $state = $this->gameState ?? GameState::initial();

        return $this->team === TeamAB::TeamA ? $state->substitutionsTeamA : $state->substitutionsTeamB;
    }

    private function gameSideResolver(): GameSideResolver
    {
        return app(GameSideResolver::class);
    }
}
