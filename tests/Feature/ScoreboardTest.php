<?php

declare(strict_types=1);

use App\Data\GameState\GameState;
use App\Enums\TeamAB;
use App\Enums\TeamSide;
use App\Livewire\Scoreboard;
use App\Models\Game;
use App\Models\Team;
use Livewire\Livewire;

test('scoreboard shows team country codes based on team a and team b toss assignment', function (): void {
    $game = gameWithDistinctTeamCountryCodes();
    $game->recordToss(TeamSide::Away, TeamAB::TeamA);

    $teamACode = $game->awayTeam->country_code;
    $teamBCode = $game->homeTeam->country_code;

    Livewire::test(Scoreboard::class, [
        'gameId' => $game->getKey(),
        'gameState' => GameState::fromAttributes([
            'sets_won_team_a' => 0,
            'sets_won_team_b' => 0,
            'score_team_a' => 14,
            'score_team_b' => 19,
        ]),
    ])
        ->assertSeeHtml('data-scoreboard-left-team="team_a"')
        ->assertSeeHtml('data-scoreboard-right-team="team_b"')
        ->assertSeeInOrder([
            $teamACode,
            'Sets',
            $teamBCode,
            '0',
            ':',
            '0',
            $teamACode,
            'Points',
            $teamBCode,
            '14',
            ':',
            '19',
        ]);
});

test('scoreboard swaps left and right teams when completed set count is odd', function (): void {
    $game = gameWithDistinctTeamCountryCodes();
    $game->recordToss(TeamSide::Home, TeamAB::TeamA);

    $teamACode = $game->homeTeam->country_code;
    $teamBCode = $game->awayTeam->country_code;

    Livewire::test(Scoreboard::class, [
        'gameId' => $game->getKey(),
        'gameState' => GameState::fromAttributes([
            'sets_won_team_a' => 2,
            'sets_won_team_b' => 1,
            'score_team_a' => 17,
            'score_team_b' => 21,
        ]),
    ])
        ->assertSeeHtml('data-scoreboard-left-team="team_b"')
        ->assertSeeHtml('data-scoreboard-right-team="team_a"')
        ->assertSeeInOrder([
            $teamBCode,
            'Sets',
            $teamACode,
            '1',
            ':',
            '2',
            $teamBCode,
            'Points',
            $teamACode,
            '21',
            ':',
            '17',
        ]);
});

function gameWithDistinctTeamCountryCodes(): Game
{
    $homeTeam = Team::factory()->create();
    $awayTeam = Team::factory()->create();

    while ($awayTeam->country_code === $homeTeam->country_code) {
        $awayTeam = Team::factory()->create();
    }

    return Game::factory()->betweenTeams($homeTeam, $awayTeam)->create();
}
