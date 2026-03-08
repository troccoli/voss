<?php

use App\Data\GameState\GameState;
use App\Enums\TeamAB;
use App\Enums\TeamSide;
use App\Jobs\RecalculateGameStateSnapshots;
use App\Models\Game;
use App\Models\Player;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

test('state snapshots are projected as game events are recorded', function (): void {
    $homeTeam = Team::factory()->create();
    $awayTeam = Team::factory()->create();
    $game = Game::factory()->betweenTeams($homeTeam, $awayTeam)->create();

    $homePlayers = Player::factory()->for($homeTeam)->count(7)->create();
    $awayPlayers = Player::factory()->for($awayTeam)->count(6)->create();

    foreach ($homePlayers as $index => $player) {
        $game->addPlayer($player, number: $index + 1);
    }

    foreach ($awayPlayers as $index => $player) {
        $game->addPlayer($player, number: $index + 1);
    }

    $homeLineup = $homePlayers
        ->take(6)
        ->mapWithKeys(fn (Player $player, int $index) => [$index + 1 => $player->getKey()])
        ->all();

    $awayLineup = $awayPlayers
        ->mapWithKeys(fn (Player $player, int $index) => [$index + 1 => $player->getKey()])
        ->all();

    $game->recordToss(TeamSide::Home, TeamAB::TeamA);
    $game->recordSetStarted();
    $game->recordLineup(1, TeamAB::TeamA, $homeLineup);
    $game->recordLineup(1, TeamAB::TeamB, $awayLineup);
    $game->recordRallyWinner(TeamAB::TeamA);
    $game->recordRallyWinner(TeamAB::TeamB);
    $game->recordTimeOut(TeamAB::TeamB);
    $game->recordSubstitution(
        TeamAB::TeamA,
        playerOut: $homePlayers->first()->getKey(),
        playerIn: $homePlayers->last()->getKey(),
    );

    /** @var GameState $state */
    $state = $game->stateAt();

    expect($state->setNumber)->toBe(1)
        ->and($state->scoreTeamA)->toBe(1)
        ->and($state->scoreTeamB)->toBe(1)
        ->and($state->setsWonTeamA)->toBe(0)
        ->and($state->setsWonTeamB)->toBe(0)
        ->and($state->timeoutsTeamA)->toBe(0)
        ->and($state->timeoutsTeamB)->toBe(1)
        ->and($state->substitutionsTeamA)->toBe(1)
        ->and($state->substitutionsTeamB)->toBe(0)
        ->and($state->servingTeam)->toBe(TeamAB::TeamB)
        ->and($state->rotationTeamA[1])->toBe($homePlayers->last()->getKey())
        ->and($state->rotationTeamB[1])->toBe($awayPlayers[1]->getKey());
});

test('recalculation job rebuilds snapshots from scratch up to a cutoff time', function (): void {
    $homeTeam = Team::factory()->create();
    $awayTeam = Team::factory()->create();
    $game = Game::factory()->betweenTeams($homeTeam, $awayTeam)->create();

    Carbon::setTestNow('2026-03-07 10:00:00');
    $game->recordToss(TeamSide::Home, TeamAB::TeamA);

    Carbon::setTestNow('2026-03-07 10:01:00');
    $game->recordSetStarted();

    Carbon::setTestNow('2026-03-07 10:02:00');
    $game->recordRallyWinner(TeamAB::TeamA);

    Carbon::setTestNow('2026-03-07 10:03:00');
    $game->recordRallyWinner(TeamAB::TeamB);

    Carbon::setTestNow();

    dispatch_sync(new RecalculateGameStateSnapshots(
        gameId: $game->getKey(),
        upTo: '2026-03-07 10:02:30',
    ));

    $latest = $game->fresh()->stateAt();

    expect($game->fresh()->stateSnapshots)->toHaveCount(3)
        ->and($latest->setNumber)->toBe(1)
        ->and($latest->scoreTeamA)->toBe(1)
        ->and($latest->scoreTeamB)->toBe(0)
        ->and($latest->servingTeam)->toBe(TeamAB::TeamA);
});
