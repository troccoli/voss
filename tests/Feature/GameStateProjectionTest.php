<?php

use App\Data\GameState\GameState;
use App\Enums\GameEventType;
use App\Enums\TeamAB;
use App\Enums\TeamSide;
use App\Events\Payloads\SetStartedPayload;
use App\Jobs\RecalculateGameStateSnapshots;
use App\Models\Game;
use App\Models\GameEvent;
use App\Models\GameStateSnapshot;
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

test('game state snapshot accepts a serialized serving team and casts it back to enum', function (): void {
    $game = Game::factory()->create();

    $event = GameEvent::withoutEvents(fn (): GameEvent => GameEvent::query()->create([
        'game_id' => $game->getKey(),
        'type' => GameEventType::SetStarted,
        'payload' => new SetStartedPayload,
        'created_at' => now(),
    ]));

    $snapshot = GameStateSnapshot::query()->create([
        'game_id' => $game->getKey(),
        'game_event_id' => $event->getKey(),
        'set_number' => 1,
        'score_team_a' => 0,
        'score_team_b' => 0,
        'sets_won_team_a' => 0,
        'sets_won_team_b' => 0,
        'timeouts_team_a' => 0,
        'timeouts_team_b' => 0,
        'substitutions_team_a' => 0,
        'substitutions_team_b' => 0,
        'serving_team' => TeamAB::TeamB->value,
        'rotation_team_a' => [],
        'rotation_team_b' => [],
        'set_in_progress' => true,
        'game_ended' => false,
        'created_at' => now(),
    ]);

    expect($snapshot->serving_team)->toBe(TeamAB::TeamB)
        ->and($snapshot->getRawOriginal('serving_team'))->toBe(TeamAB::TeamB->value);
});
