<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Data\GameState\GameState;
use App\Enums\TeamAB;
use App\Exceptions\InvalidGameEventTransition;
use App\Models\Game;
use App\Services\GameSideResolver;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Reactive;
use Livewire\Component;

class RallyWinnerControls extends Component
{
    #[Reactive]
    #[Locked]
    public ?int $gameId = null;

    #[Reactive]
    public ?GameState $gameState = null;

    public function mount(?int $gameId = null): void
    {
        abort_if($gameId === null, 404);

        $this->gameId = $gameId;
    }

    public function recordRallyWinner(string $team): void
    {
        $this->resetValidation('submit');

        $winningTeam = TeamAB::tryFrom($team);
        abort_if($winningTeam === null, 404);

        $activeGame = $this->activeGame();

        if ($activeGame === null) {
            $this->addError('submit', 'No active game is available to record the rally winner.');

            return;
        }

        try {
            $activeGame->recordRallyWinner($winningTeam);
        } catch (InvalidGameEventTransition $exception) {
            $this->addError('submit', $exception->getMessage());

            return;
        }

        $this->dispatch('game-event-recorded');
    }

    public function render(): View
    {
        $state = $this->gameState ?? GameState::initial();
        $completedSetCount = $state->setsWonTeamA + $state->setsWonTeamB;
        $leftTeam = $this->gameSideResolver()->teamOnLeft($completedSetCount);
        $rightTeam = $this->gameSideResolver()->teamOnRight($completedSetCount);

        return view('livewire.rally-winner-controls', [
            'leftTeam' => $leftTeam,
            'rightTeam' => $rightTeam,
            'canRecordRallyWinner' => $state->setInProgress && ! $state->gameEnded,
        ]);
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
