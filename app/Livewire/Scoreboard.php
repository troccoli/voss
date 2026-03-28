<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Data\GameState\GameState;
use App\Enums\TeamAB;
use App\Enums\TeamSide;
use App\Models\Game;
use App\Services\GameSideResolver;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Reactive;
use Livewire\Component;

class Scoreboard extends Component
{
    #[Reactive]
    #[Locked]
    public ?int $gameId = null;

    #[Reactive]
    public ?GameState $gameState = null;

    public function render(): View
    {
        $scoreboardState = $this->gameState ?? GameState::initial();
        $completedSets = $scoreboardState->setsWonTeamA + $scoreboardState->setsWonTeamB;
        $leftTeam = $this->gameSideResolver()->teamOnLeft($completedSets);
        $rightTeam = $this->gameSideResolver()->teamOnRight($completedSets);
        $teamCodes = $this->teamCountryCodes();

        return view('livewire.scoreboard', [
            'leftTeam' => $leftTeam,
            'rightTeam' => $rightTeam,
            'leftTeamCode' => $leftTeam === TeamAB::TeamA ? $teamCodes['team_a'] : $teamCodes['team_b'],
            'rightTeamCode' => $rightTeam === TeamAB::TeamA ? $teamCodes['team_a'] : $teamCodes['team_b'],
            'leftSets' => $leftTeam === TeamAB::TeamA ? $scoreboardState->setsWonTeamA : $scoreboardState->setsWonTeamB,
            'rightSets' => $rightTeam === TeamAB::TeamA ? $scoreboardState->setsWonTeamA : $scoreboardState->setsWonTeamB,
            'leftPoints' => $leftTeam === TeamAB::TeamA ? $scoreboardState->scoreTeamA : $scoreboardState->scoreTeamB,
            'rightPoints' => $rightTeam === TeamAB::TeamA ? $scoreboardState->scoreTeamA : $scoreboardState->scoreTeamB,
        ]);
    }

    /**
     * @return array{team_a: string, team_b: string}
     */
    private function teamCountryCodes(): array
    {
        $game = $this->activeGame();

        if ($game === null) {
            return [
                'team_a' => TeamAB::TeamA->label(),
                'team_b' => TeamAB::TeamB->label(),
            ];
        }

        if ($this->gameSideResolver()->sideForTeam($game, TeamAB::TeamA) === TeamSide::Home) {
            return [
                'team_a' => $game->homeTeam->country_code,
                'team_b' => $game->awayTeam->country_code,
            ];
        }

        return [
            'team_a' => $game->awayTeam->country_code,
            'team_b' => $game->homeTeam->country_code,
        ];
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

    private function gameSideResolver(): GameSideResolver
    {
        return app(GameSideResolver::class);
    }
}
