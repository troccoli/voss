<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Data\GameState\GameState;
use App\Enums\TeamAB;
use App\Enums\TeamSide;
use App\Models\Game;
use App\Services\CacheRepository;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
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
        $teamAOnLeft = $this->isTeamAOnLeft($scoreboardState);
        $teamCodes = $this->teamCountryCodes;

        return view('livewire.scoreboard', [
            'leftTeam' => $teamAOnLeft ? TeamAB::TeamA : TeamAB::TeamB,
            'rightTeam' => $teamAOnLeft ? TeamAB::TeamB : TeamAB::TeamA,
            'leftTeamCode' => $teamAOnLeft ? $teamCodes['team_a'] : $teamCodes['team_b'],
            'rightTeamCode' => $teamAOnLeft ? $teamCodes['team_b'] : $teamCodes['team_a'],
            'leftSets' => $teamAOnLeft ? $scoreboardState->setsWonTeamA : $scoreboardState->setsWonTeamB,
            'rightSets' => $teamAOnLeft ? $scoreboardState->setsWonTeamB : $scoreboardState->setsWonTeamA,
            'leftPoints' => $teamAOnLeft ? $scoreboardState->scoreTeamA : $scoreboardState->scoreTeamB,
            'rightPoints' => $teamAOnLeft ? $scoreboardState->scoreTeamB : $scoreboardState->scoreTeamA,
        ]);
    }

    private function isTeamAOnLeft(GameState $scoreboardState): bool
    {
        return ($scoreboardState->setsWonTeamA + $scoreboardState->setsWonTeamB) % 2 === 0;
    }

    /**
     * @return array{team_a: string, team_b: string}
     */
    #[Computed]
    public function teamCountryCodes(): array
    {
        $game = $this->activeGame;

        if ($game === null) {
            return [
                'team_a' => TeamAB::TeamA->label(),
                'team_b' => TeamAB::TeamB->label(),
            ];
        }

        $teamASide = $this->teamASideForToss;

        if ($teamASide === TeamSide::Home) {
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

    #[Computed]
    public function teamASideForToss(): TeamSide
    {
        $game = $this->activeGame;

        if ($game === null) {
            return TeamSide::Home;
        }

        $tossPayload = $this->cacheRepository()->latestTossPayload($game);

        if ($tossPayload === null) {
            return TeamSide::Home;
        }

        return $tossPayload->teamA;
    }

    #[Computed]
    public function activeGame(): ?Game
    {
        if ($this->gameId === null) {
            return null;
        }

        return Game::query()
            ->with(['homeTeam', 'awayTeam'])
            ->whereKey($this->gameId)
            ->first();
    }

    private function cacheRepository(): CacheRepository
    {
        return app(CacheRepository::class);
    }
}
