<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Data\GameState\GameState;
use App\Enums\GameEventType;
use App\Enums\TeamAB;
use App\Exceptions\InvalidGameEventTransition;
use App\Models\Game;
use App\Models\GameEvent;
use App\Services\CurrentMatchResolver;
use App\Services\GameSideResolver;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Reactive;
use Livewire\Component;

class StartSetSubmission extends Component
{
    #[Reactive]
    public ?GameState $gameState = null;

    public function startSet(): void
    {
        $this->resetValidation('startSet');

        $activeGame = $this->activeGame();

        if ($activeGame === null) {
            $this->addError('startSet', 'No active game is available to start the set.');

            return;
        }

        if ($this->setBreakIsInProgress()) {
            $this->addError('startSet', 'The interval between sets has not elapsed yet.');

            return;
        }

        if (! $this->hasStartSetPrerequisites()) {
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
        $setBreakRemainingSeconds = $this->setBreakRemainingSeconds();
        $upcomingSetNumber = $this->upcomingSetNumber();

        return view('livewire.start-set-submission', [
            'canStartSet' => $this->canStartSet(),
            'showSetBreakCountdown' => $setBreakRemainingSeconds > 0,
            'setBreakRemainingSeconds' => $setBreakRemainingSeconds,
            'setBreakCountdownLabel' => $this->formatDuration($setBreakRemainingSeconds),
            'showStartGameButton' => $this->canStartSet() && $upcomingSetNumber === 1,
            'shouldAutoStartSet' => $this->canStartSet() && $upcomingSetNumber > 1,
            'upcomingSetNumber' => $upcomingSetNumber,
        ]);
    }

    private function canStartSet(): bool
    {
        return $this->hasStartSetPrerequisites() && ! $this->setBreakIsInProgress();
    }

    private function hasStartSetPrerequisites(): bool
    {
        if ($this->activeGame() === null) {
            return false;
        }

        $activeGameState = $this->activeGameState();

        if (! $this->hasSubmittedToss($activeGameState)) {
            return false;
        }

        if ($activeGameState->setInProgress || $activeGameState->gameEnded) {
            return false;
        }

        return $this->bothLineupsSubmittedForUpcomingSet($activeGameState);
    }

    #[Computed]
    public function activeGame(): ?Game
    {
        return app(CurrentMatchResolver::class)->current();
    }

    #[Computed]
    public function activeGameState(): GameState
    {
        $activeGame = $this->activeGame();

        return $activeGame?->stateAt() ?? $this->resolvedGameState();
    }

    private function hasSubmittedToss(GameState $state): bool
    {
        return app(GameSideResolver::class)->hasRequiredToss($state);
    }

    private function bothLineupsSubmittedForUpcomingSet(GameState $state): bool
    {
        return $this->hasSubmittedLineupForTeam($state, TeamAB::TeamA)
            && $this->hasSubmittedLineupForTeam($state, TeamAB::TeamB);
    }

    private function hasSubmittedLineupForTeam(GameState $state, TeamAB $team): bool
    {
        $lineup = $team === TeamAB::TeamA
            ? $state->rotationTeamA
            : $state->rotationTeamB;

        return $lineup !== [];
    }

    private function upcomingSetNumber(): int
    {
        return $this->activeGameState()->setNumber + 1;
    }

    private function setBreakIsInProgress(): bool
    {
        return $this->setBreakRemainingSeconds() > 0;
    }

    private function setBreakRemainingSeconds(): int
    {
        $activeGame = $this->activeGame();
        $state = $this->activeGameState();

        if ($activeGame === null || $state->setNumber === 0 || $state->gameEnded) {
            return 0;
        }

        $lastSetEndedAt = $this->lastSetEndedAt($activeGame);

        if ($lastSetEndedAt === null) {
            return 0;
        }

        $remainingSeconds = now()->diffInSeconds(
            $lastSetEndedAt->addSeconds($this->betweenSetsDuration()),
            false,
        );

        return max(0, (int) $remainingSeconds);
    }

    private function lastSetEndedAt(Game $game): ?CarbonImmutable
    {
        /** @var GameEvent|null $setEndedEvent */
        $setEndedEvent = $game->events()
            ->reorder()
            ->where('type', GameEventType::SetEnded)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first();

        return $setEndedEvent?->created_at;
    }

    private function betweenSetsDuration(): int
    {
        return max(0, (int) config('game.between_sets_duration', 180));
    }

    private function formatDuration(int $seconds): string
    {
        $minutes = intdiv($seconds, 60);
        $remainingSeconds = $seconds % 60;

        return sprintf('%02d:%02d', $minutes, $remainingSeconds);
    }

    private function resolvedGameState(): GameState
    {
        return $this->gameState ?? GameState::initial();
    }
}
