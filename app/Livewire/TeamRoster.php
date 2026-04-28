<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Data\GameState\GameState;
use App\Enums\StaffRole;
use App\Enums\TeamAB;
use App\Enums\TeamSide;
use App\Exceptions\InvalidGameEventTransition;
use App\Models\Game;
use App\Services\GameSideResolver;
use App\Services\ScoresheetDataRepository;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
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

    public function requestTimeout(): void
    {
        $activeGame = $this->activeGame();

        if ($activeGame === null) {
            $this->addError('timeout', 'No active game is available to record the timeout.');

            return;
        }

        $state = $activeGame->stateAt();
        $timeoutsTaken = $this->team === TeamAB::TeamA ? $state->timeoutsTeamA : $state->timeoutsTeamB;
        $hasTimeoutLeft = $timeoutsTaken < 2;

        if ($hasTimeoutLeft) {
            try {
                $activeGame->recordTimeOut($this->team);
            } catch (InvalidGameEventTransition $exception) {
                $this->addError('timeout', $exception->getMessage());

                return;
            }

            $this->dispatch('game-event-recorded');
        }

        $this->dispatch('timeout-recorded', team: $this->team->value, hasTimeoutLeft: $hasTimeoutLeft);
    }

    public function render(): View
    {
        $teamPlayers = $this->teamPlayers();
        $rosterPlayerCount = count($teamPlayers);
        $players = $this->benchPlayers($teamPlayers);
        $lineupSubmitted = $this->hasLineupBeenSubmitted($this->team);
        $placeholderCount = $lineupSubmitted ? 0 : max(0, $rosterPlayerCount - 6);

        return view('livewire.team-roster', [
            'players' => $players,
            'showPlayerPlaceholders' => $placeholderCount > 0,
            'placeholderCount' => $placeholderCount,
            'hasRosterPlayers' => $rosterPlayerCount > 0,
            'staffMarkers' => $this->buildStaffMarkers($this->teamStaff()),
            'reverseLayout' => $this->leftSide,
            'keyPrefix' => $this->leftSide ? 'left-player' : 'right-player',
            'markerTone' => $this->team === TeamAB::TeamA ? 'bg-blue-600' : 'bg-red-600',
            'timeoutsTaken' => $this->timeoutsTaken(),
            'substitutionsTaken' => $this->substitutionsTaken(),
            'canRequestTimeout' => $this->canRequestTimeout(),
        ]);
    }

    #[Computed]
    public function activeGame(): ?Game
    {
        return Game::query()->find($this->gameId);
    }

    /**
     * @return array<int, array{
     *     player_key: int,
     *     number: int,
     *     last_name: string
     * }>
     */
    #[Computed]
    public function teamPlayers(): array
    {
        $game = $this->activeGame();

        if ($game === null) {
            return [];
        }

        return $this->scoresheetDataRepository()->playersForSide($game, $this->targetSideForTeam($game, $this->team));
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
    #[Computed]
    public function teamStaff(): array
    {
        $game = $this->activeGame();

        if ($game === null) {
            return [];
        }

        return $this->scoresheetDataRepository()->staffForSide($game, $this->targetSideForTeam($game, $this->team));
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

    private function canRequestTimeout(): bool
    {
        $state = $this->gameState ?? GameState::initial();

        return $state->setInProgress && ! $state->gameEnded;
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
