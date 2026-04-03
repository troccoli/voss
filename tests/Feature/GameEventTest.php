<?php

use App\Enums\GameEventType;
use App\Enums\TeamAB;
use App\Enums\TeamSide;
use App\Events\Payloads\GameEndedPayload;
use App\Events\Payloads\LineupSubmittedPayload;
use App\Events\Payloads\RallyEndedPayload;
use App\Events\Payloads\SetEndedPayload;
use App\Events\Payloads\SetStartedPayload;
use App\Events\Payloads\SubstitutionCompletedPayload;
use App\Events\Payloads\TimeOutRequestedPayload;
use App\Events\Payloads\TossCompletedPayload;
use App\Exceptions\InvalidGameEventTransition;
use App\Models\Game;
use App\Models\GameEvent;
use App\Models\GameStateSnapshot;
use App\Models\Player;
use App\Models\Team;
use App\Services\GameState\GameEventRuleValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function prepareActiveSet(Game $game): void
{
    $game->recordToss(TeamSide::Home, TeamAB::TeamA);
    ensureStartingLineupRoster($game);
    submitLineupsForSet($game, 1);
    $game->recordSetStarted();
}

function winSet(Game $game, TeamAB $winner): void
{
    for ($i = 0; $i < 25; $i++) {
        $game->recordRallyWinner($winner);
    }
}

/**
 * @return array<int, int>
 */
function lineupWithRosterNumbers(int $start = 1): array
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

function ensureStartingLineupRoster(Game $game): void
{
    if ($game->players()->count() > 0) {
        return;
    }

    $homePlayers = Player::factory()->for($game->homeTeam)->count(6)->create();
    foreach ($homePlayers as $index => $player) {
        $game->addPlayer($player, number: $index + 1);
    }

    $awayPlayers = Player::factory()->for($game->awayTeam)->count(6)->create();
    foreach ($awayPlayers as $index => $player) {
        $game->addPlayer($player, number: $index + 11);
    }
}

function submitLineupsForSet(Game $game, int $set): void
{
    $game->recordLineup($set, TeamAB::TeamA, lineupWithRosterNumbers(1));
    $game->recordLineup($set, TeamAB::TeamB, lineupWithRosterNumbers(11));
}

test('a toss can be recorded with the correct type and payload', function (): void {
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

test('team b is derived as the other team when team a is the away team', function (): void {
    $homeTeam = Team::factory()->create();
    $awayTeam = Team::factory()->create();
    $game = Game::factory()->betweenTeams($homeTeam, $awayTeam)->create();

    $game->recordToss(TeamSide::Away, TeamAB::TeamA);

    $event = $game->events->first();
    expect($event->payload->teamA)->toBe(TeamSide::Away);
});

test('a lineup can be recorded for a set with correct type, set number, team, and positions', function (): void {
    $homeTeam = Team::factory()->create();
    $awayTeam = Team::factory()->create();
    $game = Game::factory()->betweenTeams($homeTeam, $awayTeam)->create();

    $positions = lineupWithRosterNumbers();
    $players = Player::factory()->for($homeTeam)->count(6)->create();

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

test('events are returned in chronological insertion order', function (): void {
    $homeTeam = Team::factory()->create();
    $awayTeam = Team::factory()->create();
    $game = Game::factory()->betweenTeams($homeTeam, $awayTeam)->create();

    $positions = lineupWithRosterNumbers();
    $players = Player::factory()->for($homeTeam)->count(6)->create();

    $game->recordToss(TeamSide::Home, TeamAB::TeamA);
    foreach ($players as $i => $player) {
        $game->addPlayer($player, number: $i + 1);
    }
    $game->recordLineup(1, TeamAB::TeamA, $positions);

    expect($game->events->first()->type)->toBe(GameEventType::TossCompleted)
        ->and($game->events->last()->type)->toBe(GameEventType::LineupSubmitted);
});

test('a game event cannot be modified after creation', function (): void {
    $homeTeam = Team::factory()->create();
    $awayTeam = Team::factory()->create();
    $game = Game::factory()->betweenTeams($homeTeam, $awayTeam)->create();

    $game->recordToss(TeamSide::Home, TeamAB::TeamA);

    $event = $game->events->first();
    $event->payload = new TossCompletedPayload(teamA: TeamSide::Away, serving: TeamAB::TeamB);

    expect(fn () => $event->save())->toThrow(LogicException::class);
});

test('the type attribute is cast to a GameEventType enum', function (): void {
    $homeTeam = Team::factory()->create();
    $awayTeam = Team::factory()->create();
    $game = Game::factory()->betweenTeams($homeTeam, $awayTeam)->create();

    $game->recordToss(TeamSide::Home, TeamAB::TeamA);

    $event = GameEvent::first();
    expect($event->type)->toBeInstanceOf(GameEventType::class)
        ->and($event->type)->toBe(GameEventType::TossCompleted);
});

test('events are isolated per game', function (): void {
    $homeTeam = Team::factory()->create();
    $awayTeam = Team::factory()->create();

    $game1 = Game::factory()->betweenTeams($homeTeam, $awayTeam)->create();
    $game2 = Game::factory()->betweenTeams($homeTeam, $awayTeam)->create();

    $game1->recordToss(TeamSide::Home, TeamAB::TeamA);

    expect($game1->events)->toHaveCount(1)
        ->and($game2->events)->toHaveCount(0);
});

test('a lineup with fewer than 6 positions throws an InvalidArgumentException', function (): void {
    $homeTeam = Team::factory()->create();
    $awayTeam = Team::factory()->create();
    $game = Game::factory()->betweenTeams($homeTeam, $awayTeam)->create();

    $game->recordToss(TeamSide::Home, TeamAB::TeamA);

    $players = Player::factory()->for($homeTeam)->count(5)->create();
    foreach ($players as $i => $player) {
        $game->addPlayer($player, number: $i + 1);
    }

    $positions = [
        1 => 1,
        2 => 2,
        3 => 3,
        4 => 4,
        5 => 5,
    ];

    expect(fn () => $game->recordLineup(1, TeamAB::TeamA, $positions))
        ->toThrow(InvalidArgumentException::class, 'A lineup must have exactly 6 positions.');
});

test('a lineup with 0-based keys throws an InvalidArgumentException', function (): void {
    $homeTeam = Team::factory()->create();
    $awayTeam = Team::factory()->create();
    $game = Game::factory()->betweenTeams($homeTeam, $awayTeam)->create();

    $game->recordToss(TeamSide::Home, TeamAB::TeamA);

    $players = Player::factory()->for($homeTeam)->count(6)->create();
    foreach ($players as $i => $player) {
        $game->addPlayer($player, number: $i + 1);
    }

    $positions = [
        0 => 1,
        1 => 2,
        2 => 3,
        3 => 4,
        4 => 5,
        5 => 6,
    ];

    expect(fn () => $game->recordLineup(1, TeamAB::TeamA, $positions))
        ->toThrow(InvalidArgumentException::class, 'Lineup positions must be keyed 1 through 6.');
});

test('a lineup with a duplicate roster number throws an InvalidArgumentException', function (): void {
    $homeTeam = Team::factory()->create();
    $awayTeam = Team::factory()->create();
    $game = Game::factory()->betweenTeams($homeTeam, $awayTeam)->create();

    $game->recordToss(TeamSide::Home, TeamAB::TeamA);

    $players = Player::factory()->for($homeTeam)->count(6)->create();
    foreach ($players as $i => $player) {
        $game->addPlayer($player, number: $i + 1);
    }

    $positions = [1 => 1, 2 => 1, 3 => 3, 4 => 4, 5 => 5, 6 => 6];

    expect(fn () => $game->recordLineup(1, TeamAB::TeamA, $positions))
        ->toThrow(InvalidArgumentException::class, 'All 6 lineup positions must have different roster numbers.');
});

test('a lineup submitted before the toss is rejected', function (): void {
    $homeTeam = Team::factory()->create();
    $awayTeam = Team::factory()->create();
    $game = Game::factory()->betweenTeams($homeTeam, $awayTeam)->create();

    $positions = lineupWithRosterNumbers();

    expect(fn () => $game->recordLineup(1, TeamAB::TeamA, $positions))
        ->toThrow(InvalidGameEventTransition::class, 'A lineup cannot be submitted before the toss has been recorded.');
});

test('a lineup cannot be submitted after the set has started', function (): void {
    $homeTeam = Team::factory()->create();
    $awayTeam = Team::factory()->create();
    $game = Game::factory()->betweenTeams($homeTeam, $awayTeam)->create();

    $homePlayers = Player::factory()->for($homeTeam)->count(6)->create();
    foreach ($homePlayers as $i => $player) {
        $game->addPlayer($player, number: $i + 1);
    }

    $awayPlayers = Player::factory()->for($awayTeam)->count(6)->create();
    foreach ($awayPlayers as $i => $player) {
        $game->addPlayer($player, number: $i + 11);
    }

    $game->recordToss(TeamSide::Home, TeamAB::TeamA);
    submitLineupsForSet($game, 1);
    $game->recordSetStarted();

    $positions = lineupWithRosterNumbers();

    expect(fn () => $game->recordLineup(1, TeamAB::TeamA, $positions))
        ->toThrow(InvalidGameEventTransition::class, 'A lineup can only be submitted before the set starts.');
});

test('a lineup for the next set can be submitted after the previous set ends', function (): void {
    $homeTeam = Team::factory()->create();
    $awayTeam = Team::factory()->create();
    $game = Game::factory()->betweenTeams($homeTeam, $awayTeam)->create();

    $homePlayers = Player::factory()->for($homeTeam)->count(6)->create();
    foreach ($homePlayers as $i => $player) {
        $game->addPlayer($player, number: $i + 1);
    }

    $awayPlayers = Player::factory()->for($awayTeam)->count(6)->create();
    foreach ($awayPlayers as $i => $player) {
        $game->addPlayer($player, number: $i + 11);
    }

    $game->recordToss(TeamSide::Home, TeamAB::TeamA);
    submitLineupsForSet($game, 1);
    $game->recordSetStarted();
    winSet($game, TeamAB::TeamA);

    $positions = lineupWithRosterNumbers();
    $game->recordLineup(2, TeamAB::TeamA, $positions);

    $event = $game->events->last();
    expect($event->type)->toBe(GameEventType::LineupSubmitted)
        ->and($event->payload->set)->toBe(2);
});

test('a rally ended event can be recorded with the correct type and payload', function (): void {
    $homeTeam = Team::factory()->create();
    $awayTeam = Team::factory()->create();
    $game = Game::factory()->betweenTeams($homeTeam, $awayTeam)->create();

    prepareActiveSet($game);
    $game->recordRallyWinner(TeamAB::TeamA);

    $event = $game->events->last();
    expect($event->type)->toBe(GameEventType::RallyEnded)
        ->and($event->payload)->toBeInstanceOf(RallyEndedPayload::class)
        ->and($event->payload->team)->toBe(TeamAB::TeamA);
});

test('rally ended event stores the winning team', function (TeamAB $team): void {
    $homeTeam = Team::factory()->create();
    $awayTeam = Team::factory()->create();
    $game = Game::factory()->betweenTeams($homeTeam, $awayTeam)->create();

    prepareActiveSet($game);
    $game->recordRallyWinner($team);

    $event = $game->events->last();
    expect($event->payload->team)->toBe($team);
})->with([
    'team A' => [TeamAB::TeamA],
    'team B' => [TeamAB::TeamB],
]);

test('a lineup with a roster number not on the team roster throws an InvalidArgumentException', function (): void {
    $homeTeam = Team::factory()->create();
    $awayTeam = Team::factory()->create();
    $game = Game::factory()->betweenTeams($homeTeam, $awayTeam)->create();

    $game->recordToss(TeamSide::Home, TeamAB::TeamA);

    $homePlayers = Player::factory()->for($homeTeam)->count(6)->create();
    foreach ($homePlayers as $i => $player) {
        $game->addPlayer($player, number: $i + 1);
    }

    $awayPlayers = Player::factory()->for($awayTeam)->count(6)->create();
    foreach ($awayPlayers as $i => $player) {
        $game->addPlayer($player, number: $i + 11);
    }

    $positions = lineupWithRosterNumbers(start: 11);

    expect(fn () => $game->recordLineup(1, TeamAB::TeamA, $positions))
        ->toThrow(InvalidArgumentException::class, 'is not on the non-libero roster for the specified team.');
});

test('a lineup with non-positive roster numbers throws an InvalidArgumentException', function (): void {
    $homeTeam = Team::factory()->create();
    $awayTeam = Team::factory()->create();
    $game = Game::factory()->betweenTeams($homeTeam, $awayTeam)->create();

    $players = Player::factory()->for($homeTeam)->count(6)->create();
    foreach ($players as $i => $player) {
        $game->addPlayer($player, number: $i + 1);
    }

    $game->recordToss(TeamSide::Home, TeamAB::TeamA);

    $positions = lineupWithRosterNumbers();
    $positions[1] = 0;

    expect(fn () => $game->recordLineup(1, TeamAB::TeamA, $positions))
        ->toThrow(InvalidArgumentException::class, 'must contain a positive roster number.');
});

test('a substitution can be recorded with the correct type and payload', function (): void {
    $homeTeam = Team::factory()->create();
    $awayTeam = Team::factory()->create();
    $game = Game::factory()->betweenTeams($homeTeam, $awayTeam)->create();

    prepareActiveSet($game);
    $game->recordSubstitution(TeamAB::TeamA, playerOut: 5, playerIn: 12);

    $event = $game->events->last();
    expect($event->type)->toBe(GameEventType::SubstitutionCompleted)
        ->and($event->payload)->toBeInstanceOf(SubstitutionCompletedPayload::class)
        ->and($event->payload->team)->toBe(TeamAB::TeamA)
        ->and($event->payload->playerOut)->toBe(5)
        ->and($event->payload->playerIn)->toBe(12);
});

test('substitution event stores the correct team', function (TeamAB $team): void {
    $homeTeam = Team::factory()->create();
    $awayTeam = Team::factory()->create();
    $game = Game::factory()->betweenTeams($homeTeam, $awayTeam)->create();

    prepareActiveSet($game);
    $game->recordSubstitution($team, playerOut: 3, playerIn: 9);

    $event = $game->events->last();
    expect($event->payload->team)->toBe($team);
})->with([
    'team A' => [TeamAB::TeamA],
    'team B' => [TeamAB::TeamB],
]);

test('a time-out request can be recorded with the correct type and payload', function (): void {
    $homeTeam = Team::factory()->create();
    $awayTeam = Team::factory()->create();
    $game = Game::factory()->betweenTeams($homeTeam, $awayTeam)->create();

    prepareActiveSet($game);
    $game->recordTimeOut(TeamAB::TeamA);

    $event = $game->events->last();
    expect($event->type)->toBe(GameEventType::TimeOutRequested)
        ->and($event->payload)->toBeInstanceOf(TimeOutRequestedPayload::class)
        ->and($event->payload->team)->toBe(TeamAB::TeamA);
});

test('time-out requested event stores the requesting team', function (TeamAB $team): void {
    $homeTeam = Team::factory()->create();
    $awayTeam = Team::factory()->create();
    $game = Game::factory()->betweenTeams($homeTeam, $awayTeam)->create();

    prepareActiveSet($game);
    $game->recordTimeOut($team);

    $event = $game->events->last();
    expect($event->payload->team)->toBe($team);
})->with([
    'team A' => [TeamAB::TeamA],
    'team B' => [TeamAB::TeamB],
]);

test('a set started event can be recorded with the correct type and empty payload', function (): void {
    $homeTeam = Team::factory()->create();
    $awayTeam = Team::factory()->create();
    $game = Game::factory()->betweenTeams($homeTeam, $awayTeam)->create();

    $game->recordToss(TeamSide::Home, TeamAB::TeamA);
    ensureStartingLineupRoster($game);
    submitLineupsForSet($game, 1);
    $game->recordSetStarted();

    expect($game->events)->toHaveCount(4);

    $event = $game->events->last();
    expect($event->type)->toBe(GameEventType::SetStarted)
        ->and($event->payload)->toBeInstanceOf(SetStartedPayload::class)
        ->and($event->payload->toArray())->toBe([]);
});

test('a set ended event can be recorded with the correct type and empty payload', function (): void {
    $homeTeam = Team::factory()->create();
    $awayTeam = Team::factory()->create();
    $game = Game::factory()->betweenTeams($homeTeam, $awayTeam)->create();

    prepareActiveSet($game);
    winSet($game, TeamAB::TeamA);

    $event = $game->events->last();
    expect($event->type)->toBe(GameEventType::SetEnded)
        ->and($event->payload)->toBeInstanceOf(SetEndedPayload::class)
        ->and($event->payload->toArray())->toBe([]);
});

test('a game ended event can be recorded with the correct type and empty payload', function (): void {
    $homeTeam = Team::factory()->create();
    $awayTeam = Team::factory()->create();
    $game = Game::factory()->betweenTeams($homeTeam, $awayTeam)->create();

    $game->recordToss(TeamSide::Home, TeamAB::TeamA);
    ensureStartingLineupRoster($game);
    for ($set = 0; $set < 3; $set++) {
        submitLineupsForSet($game, $set + 1);
        $game->recordSetStarted();
        winSet($game, TeamAB::TeamA);
    }

    $event = $game->events->last();
    expect($event->type)->toBe(GameEventType::GameEnded)
        ->and($event->payload)->toBeInstanceOf(GameEndedPayload::class)
        ->and($event->payload->toArray())->toBe([]);
});

test('a set cannot start before the toss', function (): void {
    $homeTeam = Team::factory()->create();
    $awayTeam = Team::factory()->create();
    $game = Game::factory()->betweenTeams($homeTeam, $awayTeam)->create();

    expect(fn () => $game->recordSetStarted())
        ->toThrow(InvalidGameEventTransition::class, 'A set cannot start before the toss has been recorded.');
});

test('a set cannot start until both lineups are submitted for the upcoming set', function (): void {
    $homeTeam = Team::factory()->create();
    $awayTeam = Team::factory()->create();
    $game = Game::factory()->betweenTeams($homeTeam, $awayTeam)->create();

    $game->recordToss(TeamSide::Home, TeamAB::TeamA);
    ensureStartingLineupRoster($game);
    $game->recordLineup(1, TeamAB::TeamA, lineupWithRosterNumbers(1));

    expect(fn () => $game->recordSetStarted())
        ->toThrow(InvalidGameEventTransition::class, 'Both team lineups must be submitted before starting the set.');
});

test('a set cannot end before score reaches 25 with a two-point advantage', function (): void {
    $homeTeam = Team::factory()->create();
    $awayTeam = Team::factory()->create();
    $game = Game::factory()->betweenTeams($homeTeam, $awayTeam)->create();

    prepareActiveSet($game);
    for ($i = 0; $i < 24; $i++) {
        $game->recordRallyWinner(TeamAB::TeamA);
    }

    expect(fn () => $game->recordSetEnded())
        ->toThrow(InvalidGameEventTransition::class, 'A set can only end when a team has at least 25 points with a 2-point advantage.');
});

test('the deciding set cannot end before 15 points with a two-point advantage', function (): void {
    $game = Game::factory()->create();
    createSnapshotForSetScore($game, setNumber: 5, scoreTeamA: 14, scoreTeamB: 12);

    expect(fn () => app(GameEventRuleValidator::class)->assertCanRecordSetEnded($game))
        ->toThrow(InvalidGameEventTransition::class, 'A set can only end when a team has at least 15 points with a 2-point advantage.');
});

test('the deciding set can end at 15 points with a two-point advantage', function (): void {
    $game = Game::factory()->create();
    createSnapshotForSetScore($game, setNumber: 5, scoreTeamA: 15, scoreTeamB: 13);

    app(GameEventRuleValidator::class)->assertCanRecordSetEnded($game);

    expect(true)->toBeTrue();
});

test('a game cannot end before one team has won three sets', function (): void {
    $homeTeam = Team::factory()->create();
    $awayTeam = Team::factory()->create();
    $game = Game::factory()->betweenTeams($homeTeam, $awayTeam)->create();

    $game->recordToss(TeamSide::Home, TeamAB::TeamA);
    ensureStartingLineupRoster($game);
    submitLineupsForSet($game, 1);
    $game->recordSetStarted();
    winSet($game, TeamAB::TeamA);

    expect(fn () => $game->recordGameEnded())
        ->toThrow(InvalidGameEventTransition::class, 'A game can only end after one team has won three sets.');
});

function createSnapshotForSetScore(Game $game, int $setNumber, int $scoreTeamA, int $scoreTeamB): void
{
    $event = GameEvent::withoutEvents(fn (): GameEvent => GameEvent::query()->create([
        'game_id' => $game->getKey(),
        'type' => GameEventType::SetStarted,
        'payload' => new SetStartedPayload,
        'created_at' => now(),
    ]));

    GameStateSnapshot::query()->create([
        'game_id' => $game->getKey(),
        'game_event_id' => $event->getKey(),
        'set_number' => $setNumber,
        'score_team_a' => $scoreTeamA,
        'score_team_b' => $scoreTeamB,
        'sets_won_team_a' => 0,
        'sets_won_team_b' => 0,
        'timeouts_team_a' => 0,
        'timeouts_team_b' => 0,
        'substitutions_team_a' => 0,
        'substitutions_team_b' => 0,
        'serving_team' => TeamAB::TeamA->value,
        'rotation_team_a' => [],
        'rotation_team_b' => [],
        'set_in_progress' => true,
        'game_ended' => false,
        'created_at' => now(),
    ]);
}
