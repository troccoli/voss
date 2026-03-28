<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Data\GameState\GameState;
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
        return view('livewire.team-roster', [
            'players' => $this->players(),
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
            return $players;
        }

        return array_values(array_filter($players, fn (array $player): bool => ! in_array($player['number'], $onCourtNumbers, true)));
    }

    /**
     * @return array<int, array{player_key: int, number: int, last_name: string}>
     */
    private function playersForTeam(Game $game, TeamAB $team): array
    {
        $teamASide = $this->teamASideForTossSets($game);
        $targetSide = $team === TeamAB::TeamA
            ? $teamASide
            : $this->oppositeSide($teamASide);

        return $this->cacheRepository()->playersForSide($game, $targetSide);
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
}
