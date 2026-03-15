<?php

declare(strict_types=1);

namespace App\Livewire;

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
    protected CacheRepository $cacheRepository;

    #[Reactive]
    #[Locked]
    public int $gameId;

    #[Reactive]
    public TeamAB $team = TeamAB::TeamA;

    #[Reactive]
    public bool $leftSide = true;

    public function mount(
        CacheRepository $cacheRepository,
        ?int $gameId = null,
        TeamAB $team = TeamAB::TeamA,
        bool $leftSide = true,
    ): void {
        abort_if(is_null($gameId), 404);

        $this->cacheRepository = $cacheRepository;
        $this->gameId = $gameId;
        $this->team = $team;
        $this->leftSide = $leftSide;
    }

    public function render(): View
    {
        return view('livewire.team-roster', [
            'teamLabel' => $this->team->label(),
            'players' => $this->players(),
            'numberFirst' => ! $this->leftSide,
            'alignRight' => $this->leftSide,
            'keyPrefix' => $this->leftSide ? 'left-player' : 'right-player',
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

        return $this->playersForTeam($game, $this->team);
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

        return $this->cacheRepository->playersForSide($game, $targetSide);
    }

    private function teamASideForTossSets(Game $game): TeamSide
    {
        $tossPayload = $this->cacheRepository->latestTossPayload($game);

        if ($tossPayload === null) {
            return TeamSide::Home;
        }

        return $tossPayload->teamA;
    }

    private function oppositeSide(TeamSide $side): TeamSide
    {
        return $side === TeamSide::Home
            ? TeamSide::Away
            : TeamSide::Home;
    }
}
