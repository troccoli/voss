<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Enums\TeamAB;
use App\Enums\TeamSide;
use App\Exceptions\InvalidGameEventTransition;
use App\Models\Game;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Reactive;
use Livewire\Component;

class TossResultSubmission extends Component
{
    #[Reactive]
    #[Locked]
    public ?int $gameId = null;

    /** @var array<string, mixed> */
    #[Reactive]
    public array $gameState = [];

    public string $teamA = TeamSide::Home->value;

    public string $serving = TeamAB::TeamA->value;

    /**
     * @param  array<string, mixed>  $gameState
     */
    public function mount(?int $gameId = null, array $gameState = []): void
    {
        $this->gameId = $gameId;
        $this->gameState = $gameState;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function rules(): array
    {
        return [
            'teamA' => ['required', Rule::enum(TeamSide::class)],
            'serving' => ['required', Rule::enum(TeamAB::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'teamA.required' => 'Select whether Team A is home or away.',
            'serving.required' => 'Select whether Team A or Team B serves first.',
        ];
    }

    public function submit(): void
    {
        $this->validate();

        if ($this->gameId === null) {
            $this->addError('submit', 'No active game is available to record the toss.');

            return;
        }

        $activeGame = Game::query()->whereKey($this->gameId)->first();

        if ($activeGame === null) {
            $this->addError('submit', 'No active game is available to record the toss.');

            return;
        }

        try {
            $activeGame->recordToss(
                teamA: TeamSide::from($this->teamA),
                serving: TeamAB::from($this->serving),
            );
        } catch (InvalidGameEventTransition $exception) {
            $this->addError('submit', $exception->getMessage());

            return;
        }

        Flux::modal('submit-toss-result')->close();
        $this->dispatch('game-event-recorded');

        $this->resetValidation();
        $this->teamA = TeamSide::Home->value;
        $this->serving = TeamAB::TeamA->value;
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('livewire.toss-result-submission');
    }
}
