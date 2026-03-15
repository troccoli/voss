<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Enums\TeamAB;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Reactive;
use Livewire\Component;

class LineupSubmission extends Component
{
    public string $team = TeamAB::TeamA->value;

    #[Reactive]
    #[Locked]
    public ?int $gameId = null;

    /** @var array<string, mixed> */
    #[Reactive]
    public array $gameState = [];

    /** @var array<int, string> */
    public array $lineup = [];

    /**
     * @param  array<string, mixed>  $gameState
     */
    public function mount(string $team, ?int $gameId = null, array $gameState = []): void
    {
        if (! in_array($team, array_column(TeamAB::cases(), 'value'), true)) {
            throw new \InvalidArgumentException('Unsupported team value for lineup submission.');
        }

        $this->team = $team;
        $this->gameId = $gameId;
        $this->gameState = $gameState;
        $this->lineup = [
            1 => '',
            2 => '',
            3 => '',
            4 => '',
            5 => '',
            6 => '',
        ];
    }

    public function modalName(): string
    {
        return 'submit-lineup-'.$this->team;
    }

    public function buttonLabel(): string
    {
        return $this->team === TeamAB::TeamA->value
            ? 'Submit Team A Lineup'
            : 'Submit Team B Lineup';
    }

    public function modalHeading(): string
    {
        return $this->team === TeamAB::TeamA->value
            ? 'Team A Lineup'
            : 'Team B Lineup';
    }

    public function submit(): void
    {
        Flux::modal($this->modalName())->close();
    }

    public function render(): View
    {
        return view('livewire.lineup-submission');
    }
}
