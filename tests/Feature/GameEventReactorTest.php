<?php

use App\Enums\GameEventType;
use App\Enums\TeamAB;
use App\Enums\TeamSide;
use App\Models\Game;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('in sets one to four the set auto-ends at 25 points with a two-point lead', function () {
    $homeTeam = Team::factory()->create();
    $awayTeam = Team::factory()->create();
    $game = Game::factory()->betweenTeams($homeTeam, $awayTeam)->create();

    $game->recordToss(TeamSide::Home, TeamAB::TeamA);
    $game->recordSetStarted();

    for ($i = 0; $i < 24; $i++) {
        $game->recordRallyWinner(TeamAB::TeamA);
        $game->recordRallyWinner(TeamAB::TeamB);
    }

    expect($game->fresh()->events->last()->type)->toBe(GameEventType::RallyEnded)
        ->and($game->fresh()->stateAt()->setInProgress)->toBeTrue();

    $game->recordRallyWinner(TeamAB::TeamA); // 25-24

    expect($game->fresh()->events->last()->type)->toBe(GameEventType::RallyEnded)
        ->and($game->fresh()->stateAt()->setInProgress)->toBeTrue();

    $game->recordRallyWinner(TeamAB::TeamA); // 26-24

    expect($game->fresh()->events->last()->type)->toBe(GameEventType::SetEnded)
        ->and($game->fresh()->stateAt()->setInProgress)->toBeFalse();
});

test('in the fifth set the set auto-ends at 15 points with a two-point lead', function () {
    $homeTeam = Team::factory()->create();
    $awayTeam = Team::factory()->create();
    $game = Game::factory()->betweenTeams($homeTeam, $awayTeam)->create();

    $game->recordToss(TeamSide::Home, TeamAB::TeamA);

    foreach ([TeamAB::TeamA, TeamAB::TeamB, TeamAB::TeamA, TeamAB::TeamB] as $winner) {
        $game->recordSetStarted();

        for ($i = 0; $i < 25; $i++) {
            $game->recordRallyWinner($winner);
        }
    }

    $game->recordSetStarted(); // 5th set

    for ($i = 0; $i < 14; $i++) {
        $game->recordRallyWinner(TeamAB::TeamA);
        $game->recordRallyWinner(TeamAB::TeamB);
    }

    $game->recordRallyWinner(TeamAB::TeamA); // 15-14

    expect($game->fresh()->events->last()->type)->toBe(GameEventType::RallyEnded)
        ->and($game->fresh()->stateAt()->setInProgress)->toBeTrue();

    $game->recordRallyWinner(TeamAB::TeamA); // 16-14

    $state = $game->fresh()->stateAt();
    expect($game->fresh()->events->last()->type)->toBe(GameEventType::GameEnded)
        ->and($state->setInProgress)->toBeFalse()
        ->and($state->gameEnded)->toBeTrue()
        ->and($state->setsWonTeamA)->toBe(3)
        ->and($state->setsWonTeamB)->toBe(2);
});
