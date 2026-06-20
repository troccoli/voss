<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Data\GameState\GameState;
use App\Enums\TeamAB;
use App\Enums\TeamSide;
use App\Models\Game as GameModel;
use App\Services\CurrentMatchResolver;
use App\Services\GameSideResolver;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class Game extends Component
{
    public GameState $gameState;

    public ?int $justEndedSetNumber = null;

    public ?string $setWinnerCode = null;

    public ?int $finalScoreWinner = null;

    public ?int $finalScoreLoser = null;

    public bool $showFifthSetSideChangePrompt = false;

    public function mount(CurrentMatchResolver $currentMatchResolver): void
    {
        $resolvedGame = $currentMatchResolver->current();

        if ($resolvedGame === null) {
            $this->redirectRoute('match.setup', navigate: true);

            return;
        }

        if (! $currentMatchResolver->isSetupComplete($resolvedGame)) {
            $this->redirectRoute('match.setup', navigate: true);

            return;
        }

        $this->synchronizeGameContext();
    }

    #[On('game-event-recorded')]
    public function synchronizeGameContext(): void
    {
        $game = $this->activeGame();

        if ($game === null) {
            return;
        }

        $wasSetInProgress = isset($this->gameState) && $this->gameState->setInProgress;
        $previousSetNumber = isset($this->gameState) ? $this->gameState->setNumber : 0;
        $previousSetsWonTeamA = isset($this->gameState) ? $this->gameState->setsWonTeamA : 0;
        $previousScoreTeamA = isset($this->gameState) ? $this->gameState->scoreTeamA : 0;
        $previousScoreTeamB = isset($this->gameState) ? $this->gameState->scoreTeamB : 0;
        $previousShowFifthSetSideChangePrompt = $this->showFifthSetSideChangePrompt;

        $this->gameState = $game->stateAt();
        $this->showFifthSetSideChangePrompt = $this->gameSideResolver()->shouldPromptForFifthSetSideSwap($this->gameState);

        if ($wasSetInProgress && ! $this->gameState->setInProgress && ! $this->gameState->gameEnded) {
            $winnerIsTeamA = $this->gameState->setsWonTeamA > $previousSetsWonTeamA;
            $winnerTeam = $winnerIsTeamA ? TeamAB::TeamA : TeamAB::TeamB;
            $this->justEndedSetNumber = $previousSetNumber;
            $this->setWinnerCode = $this->countryCodeForTeam($game, $winnerTeam);
            $this->finalScoreWinner = $winnerIsTeamA ? $previousScoreTeamA : $previousScoreTeamB;
            $this->finalScoreLoser = $winnerIsTeamA ? $previousScoreTeamB : $previousScoreTeamA;
            Flux::modal('set-ended')->show();
        }

        if ($this->showFifthSetSideChangePrompt) {
            if (! $previousShowFifthSetSideChangePrompt) {
                Flux::modal('fifth-set-side-change')->show();
            }
        } else {
            Flux::modal('fifth-set-side-change')->close();
        }
    }

    public function acknowledgeSetEnd(): void
    {
        $this->justEndedSetNumber = null;
        $this->setWinnerCode = null;
        $this->finalScoreWinner = null;
        $this->finalScoreLoser = null;
        Flux::modal('set-ended')->close();
    }

    public function acknowledgeFifthSetSideChange(): void
    {
        $game = $this->activeGame();

        if ($game === null) {
            return;
        }

        $game->recordCourtSidesSwapped();
        $this->synchronizeGameContext();
        $this->dispatch('game-event-recorded');
    }

    public function render(): View
    {
        return view('livewire.game', [
            'isBeforeInitialToss' => $this->isBeforeInitialToss(),
            'justEndedSetNumber' => $this->justEndedSetNumber,
            'setWinnerCode' => $this->setWinnerCode,
            'finalScoreWinner' => $this->finalScoreWinner,
            'finalScoreLoser' => $this->finalScoreLoser,
            'showFifthSetSideChangePrompt' => $this->showFifthSetSideChangePrompt,
        ]);
    }

    private function countryCodeForTeam(GameModel $game, TeamAB $team): string
    {
        $resolver = app(GameSideResolver::class);

        return $resolver->sideForTeam($game, $team) === TeamSide::Home
            ? $game->homeTeam->country_code
            : $game->awayTeam->country_code;
    }

    private function gameSideResolver(): GameSideResolver
    {
        return app(GameSideResolver::class);
    }

    private function isBeforeInitialToss(): bool
    {
        return ! $this->gameSideResolver()->hasRequiredToss($this->gameState)
            && ! $this->gameSideResolver()->requiresFifthSetToss($this->gameState);
    }

    private function activeGame(): ?GameModel
    {
        return app(CurrentMatchResolver::class)->current();
    }
}
