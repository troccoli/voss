<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Data\GameState\GameState;
use App\Enums\TeamAB;
use App\Enums\TeamSide;
use App\Exceptions\InvalidGameEventTransition;
use App\Models\Game;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Reactive;
use Livewire\Component;

class TossResultSubmission extends Component
{
    #[Reactive]
    #[Locked]
    public ?int $gameId = null;

    #[Reactive]
    public ?GameState $gameState = null;

    public string $teamA = TeamSide::Home->value;

    public string $serving = TeamSide::Home->value;

    public function mount(?int $gameId = null): void
    {
        $this->gameId = $gameId;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function rules(): array
    {
        return [
            'teamA' => ['required', Rule::enum(TeamSide::class)],
            'serving' => ['required', Rule::enum(TeamSide::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'teamA.required' => 'Select whether Team A is home or away.',
            'serving.required' => 'Select whether the home or away team serves first.',
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
            $teamASide = TeamSide::from($this->teamA);
            $servingSide = TeamSide::from($this->serving);

            $activeGame->recordToss(
                teamA: $teamASide,
                serving: $servingSide === $teamASide ? TeamAB::TeamA : TeamAB::TeamB,
            );
        } catch (InvalidGameEventTransition $exception) {
            $this->addError('submit', $exception->getMessage());

            return;
        }

        Flux::modal('submit-toss-result')->close();
        $this->dispatch('game-event-recorded');

        $this->resetValidation();
        $this->teamA = TeamSide::Home->value;
        $this->serving = TeamSide::Home->value;
    }

    public function render(): View
    {
        $activeGame = $this->activeGame();

        return view('livewire.toss-result-submission', [
            'hasSubmittedToss' => $this->hasSubmittedToss($activeGame),
            'homeTeamCode' => $activeGame?->homeTeam->country_code ?? 'Home Team',
            'awayTeamCode' => $activeGame?->awayTeam->country_code ?? 'Away Team',
        ]);
    }

    private function hasSubmittedToss(?Game $activeGame = null): bool
    {
        $state = $this->resolvedGameState($activeGame);

        return $state->teamASide !== null && $state->servingTeam !== null;
    }

    private function resolvedGameState(?Game $activeGame = null): GameState
    {
        if ($activeGame !== null) {
            return $activeGame->stateAt();
        }

        if ($this->gameId === null) {
            return $this->gameState ?? GameState::initial();
        }

        $activeGame = Game::query()->whereKey($this->gameId)->first();

        return $activeGame?->stateAt() ?? ($this->gameState ?? GameState::initial());
    }

    private function activeGame(): ?Game
    {
        if ($this->gameId === null) {
            return null;
        }

        return Game::query()
            ->with(['homeTeam', 'awayTeam'])
            ->whereKey($this->gameId)
            ->first();
    }
}
