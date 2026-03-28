<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Data\GameState\GameState;
use App\Enums\StaffRole;
use App\Enums\TeamAB;
use App\Enums\TeamSide;
use App\Models\Game;
use App\Services\CacheRepository;
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
        $players = $this->players();
        $rosterPlayerCount = $this->rosterPlayerCount();
        $lineupSubmitted = $this->hasLineupBeenSubmitted($this->team);
        $placeholderCount = $lineupSubmitted ? 0 : max(0, $rosterPlayerCount - 6);

        return view('livewire.team-roster', [
            'players' => $players,
            'showPlayerPlaceholders' => $placeholderCount > 0,
            'placeholderCount' => $placeholderCount,
            'hasRosterPlayers' => $rosterPlayerCount > 0,
            'staffMarkers' => $this->staffMarkers(),
            'reverseStaffOrder' => $this->team === TeamAB::TeamB,
            'keyPrefix' => $this->leftSide ? 'left-player' : 'right-player',
            'markerTone' => $this->team === TeamAB::TeamA ? 'bg-blue-600' : 'bg-red-600',
        ]);
    }

    /**
     * @return array<int, array{
     *     player_key: int,
     *     number: int,
     *     last_name: string
     * }>
     */
    private function players(): array
    {
        $game = Game::query()->find($this->gameId);

        if ($game === null) {
            return [];
        }

        $players = $this->playersForTeam($game, $this->team);
        $onCourtNumbers = $this->onCourtRosterNumbers($this->team);

        if ($onCourtNumbers === []) {
            return [];
        }

        return array_values(array_filter($players, fn (array $player): bool => ! in_array($player['number'], $onCourtNumbers, true)));
    }

    /**
     * @return array<int, array{player_key: int, number: int, last_name: string}>
     */
    private function playersForTeam(Game $game, TeamAB $team): array
    {
        return $this->cacheRepository()->playersForSide($game, $this->targetSideForTeam($game, $team));
    }

    /**
     * @return array<int, array{role_letter: string, subscript: int|null}>
     */
    private function staffMarkers(): array
    {
        $game = Game::query()->find($this->gameId);

        if ($game === null) {
            return [];
        }

        $staff = $this->staffForTeam($game, $this->team);

        if ($staff === []) {
            return [];
        }

        $assistantCoaches = 0;
        $hasCoach = false;
        $hasDoctor = false;
        $hasTherapist = false;

        foreach ($staff as $staffMember) {
            $role = $staffMember['role'];

            if ($role === StaffRole::Coach) {
                $hasCoach = true;

                continue;
            }

            if ($role === StaffRole::AssistantCoach) {
                $assistantCoaches++;

                continue;
            }

            if ($role === StaffRole::Doctor) {
                $hasDoctor = true;

                continue;
            }

            if ($role === StaffRole::Therapist) {
                $hasTherapist = true;
            }
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

    /**
     * @return array<int, array{staff_key: int, role: StaffRole}>
     */
    private function staffForTeam(Game $game, TeamAB $team): array
    {
        return $this->cacheRepository()->staffForSide($game, $this->targetSideForTeam($game, $team));
    }

    private function teamASideForTossSets(Game $game): TeamSide
    {
        $tossPayload = $this->cacheRepository()->latestTossPayload($game);

        if ($tossPayload === null) {
            return TeamSide::Home;
        }

        return $tossPayload->teamA;
    }

    private function cacheRepository(): CacheRepository
    {
        return app(CacheRepository::class);
    }

    private function rosterPlayerCount(): int
    {
        $game = Game::query()->find($this->gameId);

        if ($game === null) {
            return 0;
        }

        return count($this->playersForTeam($game, $this->team));
    }

    private function targetSideForTeam(Game $game, TeamAB $team): TeamSide
    {
        $teamASide = $this->teamASideForTossSets($game);

        return $team === TeamAB::TeamA
            ? $teamASide
            : $this->oppositeSide($teamASide);
    }

    private function oppositeSide(TeamSide $side): TeamSide
    {
        return $side === TeamSide::Home
            ? TeamSide::Away
            : TeamSide::Home;
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
}
