<?php

declare(strict_types=1);

namespace App\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\Reactive;
use Livewire\Component;

class Court extends Component
{
    #[Reactive]
    public ?int $gameId = null;

    /** @var array<string, mixed> */
    #[Reactive]
    public array $gameState = [];

    /**
     * @param  array<string, mixed>  $gameState
     */
    public function mount(?int $gameId = null, array $gameState = []): void
    {
        $this->gameId = $gameId;
        $this->gameState = $gameState;
    }

    public function render(): View
    {
        return view('livewire.court');
    }
}
