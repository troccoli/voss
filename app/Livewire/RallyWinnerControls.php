<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Data\GameState\GameState;
use App\Enums\TeamAB;
use App\Exceptions\InvalidGameEventTransition;
use App\Models\Game;
use App\Services\CurrentMatchResolver;
use App\Services\GameSideResolver;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Reactive;
use Livewire\Component;

class RallyWinnerControls extends Component
{
    #[Reactive]
    public ?GameState $gameState = null;

    public string $side = 'left';

    public function mount(string $side = 'left'): void
    {
        $this->side = $side;
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
        $leftTeam = $this->gameSideResolver()->teamOnLeftForState($state);
        $rightTeam = $this->gameSideResolver()->teamOnRightForState($state);

        return view('livewire.rally-winner-controls', [
            'leftTeam' => $leftTeam,
            'rightTeam' => $rightTeam,
            'canRecordRallyWinner' => $state->setInProgress && ! $state->gameEnded,
            'side' => $this->side,
        ]);
    }

    private function activeGame(): ?Game
    {
        return app(CurrentMatchResolver::class)->current();
    }

    private function gameSideResolver(): GameSideResolver
    {
        return app(GameSideResolver::class);
    }
}
