<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Data\GameState\GameState;
use App\Enums\GameEventType;
use App\Enums\TeamAB;
use App\Enums\TeamSide;
use App\Events\Payloads\TossCompletedPayload;
use App\Exceptions\InvalidGameEventTransition;
use App\Models\Game;
use App\Models\GameEvent;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Reactive;
use Livewire\Component;

class Court extends Component
{
    #[Reactive]
    public ?int $gameId = null;

    #[Reactive]
    public ?GameState $gameState = null;

    public function mount(?int $gameId = null): void
    {
        $this->gameId = $gameId;
    }

    public function render(): View
    {
        return view('livewire.court', $this->courtContext());
    }

    public function recordRallyWinner(string $team): void
    {
        $this->resetValidation('rallyWinner');

        if (! $this->canRecordRallyWinner()) {
            $this->addError('rallyWinner', 'A rally result can only be recorded while a set is in progress.');

            return;
        }

        $winningTeam = TeamAB::tryFrom($team);

        if ($winningTeam === null) {
            $this->addError('rallyWinner', 'The selected team is invalid.');

            return;
        }

        $activeGame = $this->activeGame();

        if ($activeGame === null) {
            $this->addError('rallyWinner', 'No active game is available to record the rally winner.');

            return;
        }

        try {
            $activeGame->recordRallyWinner($winningTeam);
        } catch (InvalidGameEventTransition $exception) {
            $this->addError('rallyWinner', $exception->getMessage());

            return;
        }

        $this->dispatch('game-event-recorded');
    }

    /**
     * @return array{
     *     leftTeam: TeamAB,
     *     rightTeam: TeamAB,
     *     servingTeam: TeamAB|null,
     *     canRecordRallyWinner: bool,
     *     leftRotation: array<int, int>,
     *     rightRotation: array<int, int>
     * }
     */
    private function courtContext(): array
    {
        $game = $this->activeGame();
        $canRecordRallyWinner = $this->canRecordRallyWinner($game);

        $defaultContext = [
            'leftTeam' => TeamAB::TeamA,
            'rightTeam' => TeamAB::TeamB,
            'servingTeam' => $this->resolvedGameState()->servingTeam,
            'canRecordRallyWinner' => $canRecordRallyWinner,
            'leftRotation' => $this->rotationForTeam(TeamAB::TeamA),
            'rightRotation' => $this->rotationForTeam(TeamAB::TeamB),
        ];

        if ($game === null) {
            return $defaultContext;
        }

        if ($this->isTeamAOnLeft($game)) {
            return $defaultContext;
        }

        return [
            'leftTeam' => TeamAB::TeamB,
            'rightTeam' => TeamAB::TeamA,
            'servingTeam' => $this->resolvedGameState()->servingTeam,
            'canRecordRallyWinner' => $canRecordRallyWinner,
            'leftRotation' => $this->rotationForTeam(TeamAB::TeamB),
            'rightRotation' => $this->rotationForTeam(TeamAB::TeamA),
        ];
    }

    private function isTeamAOnLeft(Game $game): bool
    {
        $baseTeamAOnLeft = $this->teamASideForTossSets($game) === TeamSide::Home;
        $setNumber = $this->setNumber();

        if ($setNumber >= 2 && $setNumber <= 4) {
            return $setNumber % 2 === 1
                ? $baseTeamAOnLeft
                : ! $baseTeamAOnLeft;
        }

        return $baseTeamAOnLeft;
    }

    private function teamASideForTossSets(Game $game): TeamSide
    {
        $tossPayload = $this->latestTossPayload($game);

        if ($tossPayload === null) {
            return TeamSide::Home;
        }

        return $tossPayload->teamA;
    }

    private function setNumber(): int
    {
        return $this->resolvedGameState()->setNumber;
    }

    private function latestTossPayload(Game $game): ?TossCompletedPayload
    {
        /** @var GameEvent|null $tossEvent */
        $tossEvent = $game->events()
            ->where('type', GameEventType::TossCompleted)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first();

        if ($tossEvent === null) {
            return null;
        }

        /** @var TossCompletedPayload */
        return $tossEvent->payload;
    }

    /**
     * @return array<int, int>
     */
    private function rotationForTeam(TeamAB $team): array
    {
        return $team === TeamAB::TeamA
            ? $this->resolvedGameState()->rotationTeamA
            : $this->resolvedGameState()->rotationTeamB;
    }

    private function resolvedGameState(): GameState
    {
        return $this->gameState ?? GameState::initial();
    }

    private function canRecordRallyWinner(?Game $activeGame = null): bool
    {
        $activeGame ??= $this->activeGame();

        if ($activeGame === null) {
            return false;
        }

        $state = $this->resolvedGameState();

        return $state->setInProgress && ! $state->gameEnded;
    }

    private function activeGame(): ?Game
    {
        if ($this->gameId === null) {
            return null;
        }

        return Game::query()->whereKey($this->gameId)->first();
    }
}
