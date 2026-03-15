<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Game as GameModel;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class Game extends Component
{
    public int $gameId;

    /** @var array<string, mixed> */
    public array $gameState = [];

    public function mount(GameModel $game): void
    {
        $this->gameId = $game->getKey();
        $this->synchronizeGameContext();
    }

    #[On('game-event-recorded')]
    public function synchronizeGameContext(): void
    {
        $game = GameModel::query()->findSole($this->gameId);
        $state = $game->stateAt();

        $this->gameState = $state->toAttributes();
    }

    public function render(): View
    {
        return view('livewire.game');
    }
}
