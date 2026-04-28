<?php

use App\Enums\GameEventType;
use App\Enums\TeamAB;
use App\Enums\TeamSide;
use App\Models\Game;
use App\Models\Player;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('in sets one to four the set auto-ends at 25 points with a two-point lead', function (): void {
    $homeTeam = Team::factory()->create();
    $awayTeam = Team::factory()->create();
    $game = Game::factory()->betweenTeams($homeTeam, $awayTeam)->create();

    $game->recordToss(TeamSide::Home, TeamAB::TeamA);
    ensureRostersForSetStart($game);
    submitReactorLineupsForSet($game, 1);
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

test('in the fifth set the set auto-ends at 15 points with a two-point lead', function (): void {
    $homeTeam = Team::factory()->create();
    $awayTeam = Team::factory()->create();
    $game = Game::factory()->betweenTeams($homeTeam, $awayTeam)->create();

    $game->recordToss(TeamSide::Home, TeamAB::TeamA);
    ensureRostersForSetStart($game);

    foreach ([TeamAB::TeamA, TeamAB::TeamB, TeamAB::TeamA, TeamAB::TeamB] as $winner) {
        submitReactorLineupsForSet($game, $game->stateAt()->setNumber + 1);
        $game->recordSetStarted();

        for ($i = 0; $i < 25; $i++) {
            $game->recordRallyWinner($winner);
        }
    }

    submitReactorLineupsForSet($game, 5);
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

function ensureRostersForSetStart(Game $game): void
{
    $homePlayers = Player::factory()->for($game->homeTeam)->count(6)->create();
    foreach ($homePlayers as $index => $player) {
        $game->addPlayer($player, number: $index + 1);
    }

    $awayPlayers = Player::factory()->for($game->awayTeam)->count(6)->create();
    foreach ($awayPlayers as $index => $player) {
        $game->addPlayer($player, number: $index + 11);
    }
}

function submitReactorLineupsForSet(Game $game, int $set): void
{
    $game->recordLineup($set, TeamAB::TeamA, reactorLineupForSet(1));
    $game->recordLineup($set, TeamAB::TeamB, reactorLineupForSet(11));
}

/**
 * @return array<int, int>
 */
function reactorLineupForSet(int $start): array
{
    return [
        1 => $start,
        2 => $start + 1,
        3 => $start + 2,
        4 => $start + 3,
        5 => $start + 4,
        6 => $start + 5,
    ];
}
