<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Data\GameState\GameState;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Reactive;
use Livewire\Component;

class Scoreboard extends Component
{
    #[Reactive]
    public ?GameState $gameState = null;

    public function render(): View
    {
        return view('livewire.scoreboard', [
            'scoreboardState' => $this->gameState ?? GameState::initial(),
        ]);
    }
}
