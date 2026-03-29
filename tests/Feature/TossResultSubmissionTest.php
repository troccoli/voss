<?php

declare(strict_types=1);

use App\Enums\GameEventType;
use App\Enums\TeamAB;
use App\Enums\TeamSide;
use App\Events\Payloads\SetStartedPayload;
use App\Livewire\TossResultSubmission;
use App\Models\Game;
use App\Models\GameEvent;
use App\Models\GameStateSnapshot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('submitting toss result creates a toss completed event', function (): void {
    $game = Game::factory()->create();

    Livewire::test(TossResultSubmission::class, ['gameId' => $game->getKey()])
        ->set('teamA', TeamSide::Away->value)
        ->set('serving', TeamSide::Home->value)
        ->call('submit')
        ->assertHasNoErrors();

    $event = $game->fresh()->events->first();

    expect($event)->not->toBeNull()
        ->and($event->type)->toBe(GameEventType::TossCompleted)
        ->and($event->payload->teamA)->toBe(TeamSide::Away)
        ->and($event->payload->serving)->toBe(TeamAB::TeamB);
});

test('submitting toss result fails when there is no active game', function (): void {
    Livewire::test(TossResultSubmission::class)
        ->call('submit')
        ->assertHasErrors(['submit']);
});

test('submitting toss result fails when toss has already been recorded', function (): void {
    $game = Game::factory()->create();
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
    $game = Game::factory()->create();

    Livewire::test(TossResultSubmission::class, ['gameId' => $game->getKey()])
        ->assertSee($game->homeTeam->country_code)
        ->assertSee($game->awayTeam->country_code)
        ->assertDontSee('Home Team')
        ->assertDontSee('Away Team');
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
