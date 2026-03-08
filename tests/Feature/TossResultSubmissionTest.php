<?php

declare(strict_types=1);

use App\Enums\GameEventType;
use App\Enums\TeamAB;
use App\Enums\TeamSide;
use App\Livewire\TossResultSubmission;
use App\Models\Game;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('submitting toss result creates a toss completed event', function (): void {
    $game = Game::factory()->create();

    Livewire::test(TossResultSubmission::class, ['gameId' => $game->getKey()])
        ->set('teamA', TeamSide::Away->value)
        ->set('serving', TeamAB::TeamB->value)
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
        ->set('serving', TeamAB::TeamB->value)
        ->call('submit')
        ->assertHasErrors(['submit']);

    expect($game->fresh()->events)->toHaveCount(1);
});

test('submitting toss result records the event against the provided game id', function (): void {
    $targetGame = Game::factory()->create();
    $otherGame = Game::factory()->create();

    Livewire::test(TossResultSubmission::class, ['gameId' => $targetGame->getKey()])
        ->set('teamA', TeamSide::Away->value)
        ->set('serving', TeamAB::TeamA->value)
        ->call('submit')
        ->assertHasNoErrors();

    expect($targetGame->fresh()->events)->toHaveCount(1)
        ->and($otherGame->fresh()->events)->toHaveCount(0);
});
