<?php

use App\Enums\GameEventType;
use App\Enums\TeamAB;
use App\Enums\TeamSide;
use App\Events\Payloads\LineupSubmittedPayload;
use App\Events\Payloads\RallyWonPayload;
use App\Events\Payloads\SetWonPayload;
use App\Events\Payloads\SubstitutionCompletedPayload;
use App\Events\Payloads\TimeOutRequestedPayload;
use App\Events\Payloads\TossCompletedPayload;
use App\Models\Game;
use App\Models\GameEvent;
use App\Models\Player;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a toss can be recorded with the correct type and payload', function () {
    $homeTeam = Team::factory()->create();
    $awayTeam = Team::factory()->create();
    $game = Game::factory()->betweenTeams($homeTeam, $awayTeam)->create();

    $game->recordToss(TeamSide::Home, TeamAB::TeamA);

    expect($game->events)->toHaveCount(1);

    $event = $game->events->first();
    expect($event->type)->toBe(GameEventType::TossCompleted)
        ->and($event->payload)->toBeInstanceOf(TossCompletedPayload::class)
        ->and($event->payload->teamA)->toBe(TeamSide::Home)
        ->and($event->payload->serving)->toBe(TeamAB::TeamA);
});

test('team b is derived as the other team when team a is the away team', function () {
    $homeTeam = Team::factory()->create();
    $awayTeam = Team::factory()->create();
    $game = Game::factory()->betweenTeams($homeTeam, $awayTeam)->create();

    $game->recordToss(TeamSide::Away, TeamAB::TeamA);

    $event = $game->events->first();
    expect($event->payload->teamA)->toBe(TeamSide::Away);
});

test('a lineup can be recorded for a set with correct type, set number, team, and positions', function () {
    $homeTeam = Team::factory()->create();
    $awayTeam = Team::factory()->create();
    $game = Game::factory()->betweenTeams($homeTeam, $awayTeam)->create();

    $players = Player::factory()->for($homeTeam)->count(6)->create();
    $positions = $players->mapWithKeys(fn (Player $player, int $index) => [$index + 1 => $player->getKey()])->all();

    $game->recordToss(TeamSide::Home, TeamAB::TeamA);
    foreach ($players as $i => $player) {
        $game->addPlayer($player, number: $i + 1);
    }

    $game->recordLineup(1, TeamAB::TeamA, $positions);

    expect($game->events)->toHaveCount(2);

    $event = $game->events->last();
    expect($event->type)->toBe(GameEventType::LineupSubmitted)
        ->and($event->payload)->toBeInstanceOf(LineupSubmittedPayload::class)
        ->and($event->payload->set)->toBe(1)
        ->and($event->payload->team)->toBe(TeamAB::TeamA)
        ->and($event->payload->positions)->toBe($positions);
});

test('events are returned in chronological insertion order', function () {
    $homeTeam = Team::factory()->create();
    $awayTeam = Team::factory()->create();
    $game = Game::factory()->betweenTeams($homeTeam, $awayTeam)->create();

    $players = Player::factory()->for($homeTeam)->count(6)->create();
    $positions = $players->mapWithKeys(fn (Player $player, int $index) => [$index + 1 => $player->getKey()])->all();

    $game->recordToss(TeamSide::Home, TeamAB::TeamA);
    foreach ($players as $i => $player) {
        $game->addPlayer($player, number: $i + 1);
    }
    $game->recordLineup(1, TeamAB::TeamA, $positions);

    expect($game->events->first()->type)->toBe(GameEventType::TossCompleted)
        ->and($game->events->last()->type)->toBe(GameEventType::LineupSubmitted);
});

test('a game event cannot be modified after creation', function () {
    $homeTeam = Team::factory()->create();
    $awayTeam = Team::factory()->create();
    $game = Game::factory()->betweenTeams($homeTeam, $awayTeam)->create();

    $game->recordToss(TeamSide::Home, TeamAB::TeamA);

    $event = $game->events->first();
    $event->payload = new TossCompletedPayload(teamA: TeamSide::Away, serving: TeamAB::TeamB);

    expect(fn () => $event->save())->toThrow(\LogicException::class);
});

test('the type attribute is cast to a GameEventType enum', function () {
    $homeTeam = Team::factory()->create();
    $awayTeam = Team::factory()->create();
    $game = Game::factory()->betweenTeams($homeTeam, $awayTeam)->create();

    $game->recordToss(TeamSide::Home, TeamAB::TeamA);

    $event = GameEvent::first();
    expect($event->type)->toBeInstanceOf(GameEventType::class)
        ->and($event->type)->toBe(GameEventType::TossCompleted);
});

test('events are isolated per game', function () {
    $homeTeam = Team::factory()->create();
    $awayTeam = Team::factory()->create();

    $game1 = Game::factory()->betweenTeams($homeTeam, $awayTeam)->create();
    $game2 = Game::factory()->betweenTeams($homeTeam, $awayTeam)->create();

    $game1->recordToss(TeamSide::Home, TeamAB::TeamA);

    expect($game1->events)->toHaveCount(1)
        ->and($game2->events)->toHaveCount(0);
});

test('a lineup with fewer than 6 positions throws an InvalidArgumentException', function () {
    $homeTeam = Team::factory()->create();
    $awayTeam = Team::factory()->create();
    $game = Game::factory()->betweenTeams($homeTeam, $awayTeam)->create();

    $game->recordToss(TeamSide::Home, TeamAB::TeamA);

    $players = Player::factory()->for($homeTeam)->count(5)->create();
    foreach ($players as $i => $player) {
        $game->addPlayer($player, number: $i + 1);
    }

    $positions = $players->mapWithKeys(fn (Player $player, int $index) => [$index + 1 => $player->getKey()])->all();

    expect(fn () => $game->recordLineup(1, TeamAB::TeamA, $positions))
        ->toThrow(\InvalidArgumentException::class, 'A lineup must have exactly 6 positions.');
});

test('a lineup with 0-based keys throws an InvalidArgumentException', function () {
    $homeTeam = Team::factory()->create();
    $awayTeam = Team::factory()->create();
    $game = Game::factory()->betweenTeams($homeTeam, $awayTeam)->create();

    $game->recordToss(TeamSide::Home, TeamAB::TeamA);

    $players = Player::factory()->for($homeTeam)->count(6)->create();
    foreach ($players as $i => $player) {
        $game->addPlayer($player, number: $i + 1);
    }

    $positions = $players->mapWithKeys(fn (Player $player, int $index) => [$index => $player->getKey()])->all();

    expect(fn () => $game->recordLineup(1, TeamAB::TeamA, $positions))
        ->toThrow(\InvalidArgumentException::class, 'Lineup positions must be keyed 1 through 6.');
});

test('a lineup with a duplicate player ID throws an InvalidArgumentException', function () {
    $homeTeam = Team::factory()->create();
    $awayTeam = Team::factory()->create();
    $game = Game::factory()->betweenTeams($homeTeam, $awayTeam)->create();

    $game->recordToss(TeamSide::Home, TeamAB::TeamA);

    $players = Player::factory()->for($homeTeam)->count(6)->create();
    foreach ($players as $i => $player) {
        $game->addPlayer($player, number: $i + 1);
    }

    $firstPlayerId = $players->first()->getKey();
    $positions = [1 => $firstPlayerId, 2 => $firstPlayerId, 3 => $players[2]->getKey(), 4 => $players[3]->getKey(), 5 => $players[4]->getKey(), 6 => $players[5]->getKey()];

    expect(fn () => $game->recordLineup(1, TeamAB::TeamA, $positions))
        ->toThrow(\InvalidArgumentException::class, 'All 6 lineup positions must have different players.');
});

test('a lineup submitted before the toss throws a LogicException', function () {
    $homeTeam = Team::factory()->create();
    $awayTeam = Team::factory()->create();
    $game = Game::factory()->betweenTeams($homeTeam, $awayTeam)->create();

    $players = Player::factory()->for($homeTeam)->count(6)->create();
    $positions = $players->mapWithKeys(fn (Player $player, int $index) => [$index + 1 => $player->getKey()])->all();

    expect(fn () => $game->recordLineup(1, TeamAB::TeamA, $positions))
        ->toThrow(\LogicException::class, 'A lineup cannot be submitted before the toss has been recorded.');
});

test('a rally outcome can be recorded with the correct type and payload', function () {
    $homeTeam = Team::factory()->create();
    $awayTeam = Team::factory()->create();
    $game = Game::factory()->betweenTeams($homeTeam, $awayTeam)->create();

    $game->recordRallyWon(TeamAB::TeamA);

    expect($game->events)->toHaveCount(1);

    $event = $game->events->first();
    expect($event->type)->toBe(GameEventType::RallyWon)
        ->and($event->payload)->toBeInstanceOf(RallyWonPayload::class)
        ->and($event->payload->team)->toBe(TeamAB::TeamA);
});

test('rally won event stores the winning team', function (TeamAB $team) {
    $homeTeam = Team::factory()->create();
    $awayTeam = Team::factory()->create();
    $game = Game::factory()->betweenTeams($homeTeam, $awayTeam)->create();

    $game->recordRallyWon($team);

    $event = $game->events->first();
    expect($event->payload->team)->toBe($team);
})->with([
    'team A' => [TeamAB::TeamA],
    'team B' => [TeamAB::TeamB],
]);

test('a lineup with a player not on the team roster throws an InvalidArgumentException', function () {
    $homeTeam = Team::factory()->create();
    $awayTeam = Team::factory()->create();
    $game = Game::factory()->betweenTeams($homeTeam, $awayTeam)->create();

    $game->recordToss(TeamSide::Home, TeamAB::TeamA);

    $awayPlayers = Player::factory()->for($awayTeam)->count(6)->create();
    foreach ($awayPlayers as $i => $player) {
        $game->addPlayer($player, number: $i + 1);
    }

    $positions = $awayPlayers->mapWithKeys(fn (Player $player, int $index) => [$index + 1 => $player->getKey()])->all();

    expect(fn () => $game->recordLineup(1, TeamAB::TeamA, $positions))
        ->toThrow(\InvalidArgumentException::class, 'is not on the roster for the specified team.');
});

test('a substitution can be recorded with the correct type and payload', function () {
    $homeTeam = Team::factory()->create();
    $awayTeam = Team::factory()->create();
    $game = Game::factory()->betweenTeams($homeTeam, $awayTeam)->create();

    $game->recordSubstitution(TeamAB::TeamA, playerOut: 5, playerIn: 12);

    expect($game->events)->toHaveCount(1);

    $event = $game->events->first();
    expect($event->type)->toBe(GameEventType::SubstitutionCompleted)
        ->and($event->payload)->toBeInstanceOf(SubstitutionCompletedPayload::class)
        ->and($event->payload->team)->toBe(TeamAB::TeamA)
        ->and($event->payload->playerOut)->toBe(5)
        ->and($event->payload->playerIn)->toBe(12);
});

test('substitution event stores the correct team', function (TeamAB $team) {
    $homeTeam = Team::factory()->create();
    $awayTeam = Team::factory()->create();
    $game = Game::factory()->betweenTeams($homeTeam, $awayTeam)->create();

    $game->recordSubstitution($team, playerOut: 3, playerIn: 9);

    $event = $game->events->first();
    expect($event->payload->team)->toBe($team);
})->with([
    'team A' => [TeamAB::TeamA],
    'team B' => [TeamAB::TeamB],
]);

test('a time-out request can be recorded with the correct type and payload', function () {
    $homeTeam = Team::factory()->create();
    $awayTeam = Team::factory()->create();
    $game = Game::factory()->betweenTeams($homeTeam, $awayTeam)->create();

    $game->recordTimeOut(TeamAB::TeamA);

    expect($game->events)->toHaveCount(1);

    $event = $game->events->first();
    expect($event->type)->toBe(GameEventType::TimeOutRequested)
        ->and($event->payload)->toBeInstanceOf(TimeOutRequestedPayload::class)
        ->and($event->payload->team)->toBe(TeamAB::TeamA);
});

test('time-out requested event stores the requesting team', function (TeamAB $team) {
    $homeTeam = Team::factory()->create();
    $awayTeam = Team::factory()->create();
    $game = Game::factory()->betweenTeams($homeTeam, $awayTeam)->create();

    $game->recordTimeOut($team);

    $event = $game->events->first();
    expect($event->payload->team)->toBe($team);
})->with([
    'team A' => [TeamAB::TeamA],
    'team B' => [TeamAB::TeamB],
]);

test('a set won event can be recorded with the correct type and empty payload', function () {
    $homeTeam = Team::factory()->create();
    $awayTeam = Team::factory()->create();
    $game = Game::factory()->betweenTeams($homeTeam, $awayTeam)->create();

    $game->recordSetWon();

    expect($game->events)->toHaveCount(1);

    $event = $game->events->first();
    expect($event->type)->toBe(GameEventType::SetWon)
        ->and($event->payload)->toBeInstanceOf(SetWonPayload::class)
        ->and($event->payload->toArray())->toBe([]);
});
