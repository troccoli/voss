<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Data\GameState\GameState;
use App\Enums\TeamAB;
use App\Models\Game;
use App\Services\GameSideResolver;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Reactive;
use Livewire\Component;

class Court extends Component
{
    #[Reactive]
    public ?int $gameId = null;

    #[Reactive]
    public ?GameState $gameState = null;

    public function mount(?int $gameId = null): void
    {
        $this->gameId = $gameId;
    }

    public function render(): View
    {
        return view('livewire.court', $this->courtContext());
    }

    /**
     * @return array{
     *     leftTeam: TeamAB,
     *     rightTeam: TeamAB,
     *     servingTeam: TeamAB|null,
     *     showRosters: bool,
     *     leftRotation: array<int, int>,
     *     rightRotation: array<int, int>
     * }
     */
    private function courtContext(): array
    {
        $game = $this->activeGame();
        $completedSetCount = $this->completedSetCount();
        $leftTeam = $this->gameSideResolver()->teamOnLeft($completedSetCount);
        $rightTeam = $this->gameSideResolver()->teamOnRight($completedSetCount);
        $showRosters = $game !== null && $this->gameSideResolver()->hasRecordedToss($game);

        return [
            'leftTeam' => $leftTeam,
            'rightTeam' => $rightTeam,
            'servingTeam' => $this->resolvedGameState()->servingTeam,
            'showRosters' => $showRosters,
            'leftRotation' => $this->rotationForTeam($leftTeam),
            'rightRotation' => $this->rotationForTeam($rightTeam),
        ];
    }

    /**
     * @return array<int, int>
     */
    private function rotationForTeam(TeamAB $team): array
    {
        return $team === TeamAB::TeamA
            ? $this->resolvedGameState()->rotationTeamA
            : $this->resolvedGameState()->rotationTeamB;
    }

    private function resolvedGameState(): GameState
    {
        return $this->gameState ?? GameState::initial();
    }

    private function completedSetCount(): int
    {
        $state = $this->resolvedGameState();

        return $state->setsWonTeamA + $state->setsWonTeamB;
    }

    private function activeGame(): ?Game
    {
        if ($this->gameId === null) {
            return null;
        }

        return Game::query()->whereKey($this->gameId)->first();
    }

    private function gameSideResolver(): GameSideResolver
    {
        return app(GameSideResolver::class);
    }
}
