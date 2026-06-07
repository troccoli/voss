<?php

declare(strict_types=1);

use App\Enums\GameEventType;
use App\Enums\TeamAB;
use App\Enums\TeamSide;
use App\Events\Payloads\SetStartedPayload;
use App\Livewire\RosterSubmission;
use App\Livewire\TossResultSubmission;
use App\Models\Game;
use App\Models\GameEvent;
use App\Models\GameStateSnapshot;
use App\Models\Player;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('game.between_sets_duration', 0);
});

test('submitting toss result creates a toss completed event', function (): void {
    $game = Game::factory()->create();
    seedRosterableTeams($game);

    Livewire::test(TossResultSubmission::class, ['gameId' => $game->getKey()])
        ->set('teamA', TeamSide::Away->value)
        ->set('serving', TeamSide::Home->value)
        ->call('submit')
        ->assertHasNoErrors();

    $event = $game->fresh()->events->first();

    expect($event)->not->toBeNull()
        ->and($event->type)->toBe(GameEventType::TossCompleted)
        ->and($event->payload->teamA)->toBe(TeamSide::Away)
        ->and($event->payload->leftTeam)->toBe(TeamAB::TeamA)
        ->and($event->payload->serving)->toBe(TeamAB::TeamB);
});

test('submitting fifth set toss keeps team assignment and records left and serving teams', function (): void {
    $game = tiedGameReadyForFifthSetToss();

    Livewire::test(TossResultSubmission::class, ['gameId' => $game->getKey()])
        ->assertSee('Submit Fifth Set Toss')
        ->set('left', TeamSide::Away->value)
        ->set('serving', TeamSide::Away->value)
        ->call('submit')
        ->assertHasNoErrors();

    $event = $game->fresh()->events->last();
    $state = $game->fresh()->stateAt();

    expect($event)->not->toBeNull()
        ->and($event->type)->toBe(GameEventType::TossCompleted)
        ->and($event->payload->teamA)->toBe(TeamSide::Home)
        ->and($event->payload->leftTeam)->toBe(TeamAB::TeamB)
        ->and($event->payload->serving)->toBe(TeamAB::TeamB)
        ->and($state->fifthSetLeftTeam)->toBe(TeamAB::TeamB)
        ->and($state->servingTeam)->toBe(TeamAB::TeamB);
});

test('submitting toss result fails when there is no active game', function (): void {
    Livewire::test(TossResultSubmission::class)
        ->call('submit')
        ->assertHasErrors(['submit']);
});

test('submitting toss result fails when toss has already been recorded', function (): void {
    $game = Game::factory()->create();
    seedRosterableTeams($game);
    $game->recordToss(TeamSide::Home, TeamAB::TeamA);

    Livewire::test(TossResultSubmission::class, ['gameId' => $game->getKey()])
        ->set('teamA', TeamSide::Away->value)
        ->set('serving', TeamSide::Home->value)
        ->call('submit')
        ->assertHasErrors(['submit']);

    expect($game->fresh()->events)->toHaveCount(1);
});

test('submitting toss result records the event against the provided game id', function (): void {
    $targetGame = Game::factory()->create();
    $otherGame = Game::factory()->create();
    seedRosterableTeams($targetGame);

    Livewire::test(TossResultSubmission::class, ['gameId' => $targetGame->getKey()])
        ->set('teamA', TeamSide::Away->value)
        ->set('serving', TeamSide::Away->value)
        ->call('submit')
        ->assertHasNoErrors();

    expect($targetGame->fresh()->events)->toHaveCount(1)
        ->and($targetGame->fresh()->events->first()?->payload->serving)->toBe(TeamAB::TeamA)
        ->and($otherGame->fresh()->events)->toHaveCount(0);
});

test('toss modal shows home and away team country codes', function (): void {
    $game = tiedGameReadyForFifthSetToss();

    Livewire::test(TossResultSubmission::class, ['gameId' => $game->getKey()])
        ->assertSee($game->homeTeam->country_code)
        ->assertSee($game->awayTeam->country_code)
        ->assertDontSee('Home Team')
        ->assertDontSee('Away Team');
});

test('initial toss submission fails until rosters have been submitted', function (): void {
    $game = Game::factory()->create();

    Livewire::test(TossResultSubmission::class, ['gameId' => $game->getKey()])
        ->set('teamA', TeamSide::Away->value)
        ->set('serving', TeamSide::Home->value)
        ->call('submit')
        ->assertHasErrors(['submit']);
});

test('toss submit button is hidden when toss has already been recorded', function (): void {
    $game = Game::factory()->create();
    $game->recordToss(TeamSide::Home, TeamAB::TeamA);

    Livewire::test(TossResultSubmission::class, ['gameId' => $game->getKey()])
        ->assertDontSee('Submit Toss Result')
        ->assertDontSee('Save Toss Result');
});

test('toss submit button is hidden when snapshot state already includes toss data', function (): void {
    $game = Game::factory()->create();

    $stateEvent = GameEvent::withoutEvents(fn (): GameEvent => GameEvent::query()->create([
        'game_id' => $game->getKey(),
        'type' => GameEventType::SetStarted,
        'payload' => new SetStartedPayload,
        'created_at' => Carbon::now(),
    ]));

    GameStateSnapshot::query()->create([
        'game_id' => $game->getKey(),
        'game_event_id' => $stateEvent->getKey(),
        'set_number' => 0,
        'score_team_a' => 0,
        'score_team_b' => 0,
        'sets_won_team_a' => 0,
        'sets_won_team_b' => 0,
        'timeouts_team_a' => 0,
        'timeouts_team_b' => 0,
        'substitutions_team_a' => 0,
        'substitutions_team_b' => 0,
        'team_a_side' => TeamSide::Away->value,
        'serving_team' => TeamAB::TeamB->value,
        'rotation_team_a' => [],
        'rotation_team_b' => [],
        'set_in_progress' => false,
        'game_ended' => false,
        'created_at' => Carbon::now(),
    ]);

    Livewire::test(TossResultSubmission::class, ['gameId' => $game->getKey()])
        ->assertDontSee('Submit Toss Result')
        ->assertDontSee('Save Toss Result');
});

function seedRosterableTeams(Game $game): void
{
    $homePlayers = Player::factory()->for($game->homeTeam)->count(6)->create();
    $awayPlayers = Player::factory()->for($game->awayTeam)->count(6)->create();

    $component = Livewire::test(RosterSubmission::class, ['gameId' => $game->getKey()]);

    foreach ($homePlayers as $index => $player) {
        $component->set("homeRosterInputs.{$player->getKey()}", (string) ($index + 1));
    }

    foreach ($awayPlayers as $index => $player) {
        $component->set("awayRosterInputs.{$player->getKey()}", (string) ($index + 11));
    }

    $component->call('submitRosters')->assertHasNoErrors();
}

function tiedGameReadyForFifthSetToss(): Game
{
    $game = Game::factory()->create();

    for ($index = 0; $index < 6; $index++) {
        $homePlayer = Player::factory()->for($game->homeTeam)->create();
        $awayPlayer = Player::factory()->for($game->awayTeam)->create();
        $game->addPlayer($homePlayer, number: $index + 1);
        $game->addPlayer($awayPlayer, number: $index + 11);
    }

    $game->recordToss(TeamSide::Home, TeamAB::TeamA);

    foreach ([TeamAB::TeamA, TeamAB::TeamB, TeamAB::TeamA, TeamAB::TeamB] as $setWinner) {
        $set = $game->stateAt()->setNumber + 1;
        $game->recordLineup($set, TeamAB::TeamA, tossResultLineupPositions(1));
        $game->recordLineup($set, TeamAB::TeamB, tossResultLineupPositions(11));
        $game->recordSetStarted();

        for ($rally = 0; $rally < 25; $rally++) {
            $game->recordRallyWinner($setWinner);
        }
    }

    return $game->fresh();
}

/**
 * @return array<int, int>
 */
function tossResultLineupPositions(int $start): array
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
