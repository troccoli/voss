<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Enums\TeamAB;
use App\Exceptions\InvalidGameEventTransition;
use App\Models\Game;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Reactive;
use Livewire\Component;

class RallyWinnerButton extends Component
{
    #[Reactive]
    #[Locked]
    public ?int $gameId = null;

    public TeamAB $team = TeamAB::TeamA;

    public string $side = 'left';

    public function mount(TeamAB $team, string $side, ?int $gameId = null): void
    {
        abort_if($gameId === null, 404);
        abort_unless(in_array($side, ['left', 'right'], true), 404);

        $this->gameId = $gameId;
        $this->team = $team;
        $this->side = $side;
    }

    public function recordRallyWinner(): void
    {
        $this->resetValidation('submit');

        $activeGame = $this->activeGame();

        if ($activeGame === null) {
            $this->addError('submit', 'No active game is available to record the rally winner.');

            return;
        }

        try {
            $activeGame->recordRallyWinner($this->team);
        } catch (InvalidGameEventTransition $exception) {
            $this->addError('submit', $exception->getMessage());

            return;
        }

        $this->dispatch('game-event-recorded');
    }

    public function render(): View
    {
        return view('livewire.rally-winner-button');
    }

    private function activeGame(): ?Game
    {
        if ($this->gameId === null) {
            return null;
        }

        return Game::query()->whereKey($this->gameId)->first();
    }
}
