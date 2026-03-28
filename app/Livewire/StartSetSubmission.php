<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Data\GameState\GameState;
use App\Enums\GameEventType;
use App\Enums\TeamAB;
use App\Exceptions\InvalidGameEventTransition;
use App\Models\Game;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Reactive;
use Livewire\Component;

class StartSetSubmission extends Component
{
    #[Reactive]
    #[Locked]
    public ?int $gameId = null;

    #[Reactive]
    public ?GameState $gameState = null;

    public function mount(?int $gameId = null): void
    {
        $this->gameId = $gameId;
    }

    public function startSet(): void
    {
        $this->resetValidation('startSet');

        $activeGame = $this->activeGame();

        if ($activeGame === null) {
            $this->addError('startSet', 'No active game is available to start the set.');

            return;
        }

        if (! $this->bothLineupsSubmittedForUpcomingSet($activeGame)) {
            $this->addError('startSet', 'Both team lineups must be submitted before starting the set.');

            return;
        }

        try {
            $activeGame->recordSetStarted();
        } catch (InvalidGameEventTransition $exception) {
            $this->addError('startSet', $exception->getMessage());

            return;
        }

        $this->dispatch('game-event-recorded');
    }

    public function render(): View
    {
        return view('livewire.start-set-submission', [
            'canStartSet' => $this->canStartSet(),
            'upcomingSetNumber' => $this->upcomingSetNumber(),
        ]);
    }

    private function canStartSet(): bool
    {
        $activeGame = $this->activeGame();

        if ($activeGame === null) {
            return false;
        }

        $activeGameState = $this->activeGameState();

        if ($activeGameState->setInProgress || $activeGameState->gameEnded) {
            return false;
        }

        return $this->bothLineupsSubmittedForSet(
            game: $activeGame,
            setNumber: $activeGameState->setNumber + 1,
        );
    }

    #[Computed]
    public function activeGame(): ?Game
    {
        if ($this->gameId === null) {
            return null;
        }

        return Game::query()->whereKey($this->gameId)->first();
    }

    #[Computed]
    public function activeGameState(): GameState
    {
        $activeGame = $this->activeGame();

        return $activeGame?->stateAt() ?? $this->resolvedGameState();
    }

    private function bothLineupsSubmittedForUpcomingSet(Game $game): bool
    {
        return $this->bothLineupsSubmittedForSet(
            game: $game,
            setNumber: $this->upcomingSetNumberForGame($game),
        );
    }

    private function bothLineupsSubmittedForSet(Game $game, int $setNumber): bool
    {
        return $this->hasSubmittedLineupForSet($game, TeamAB::TeamA, $setNumber)
            && $this->hasSubmittedLineupForSet($game, TeamAB::TeamB, $setNumber);
    }

    private function hasSubmittedLineupForSet(Game $game, TeamAB $team, int $setNumber): bool
    {
        return $game->events()
            ->where('type', GameEventType::LineupSubmitted)
            ->where('payload->set', $setNumber)
            ->where('payload->team', $team->value)
            ->exists();
    }

    private function upcomingSetNumber(): int
    {
        return $this->activeGameState()->setNumber + 1;
    }

    private function upcomingSetNumberForGame(Game $game): int
    {
        return $game->stateAt()->setNumber + 1;
    }

    private function resolvedGameState(): GameState
    {
        return $this->gameState ?? GameState::initial();
    }
}
