<?php

declare(strict_types=1);

use App\Enums\TeamAB;
use App\Livewire\LineupSubmission;
use App\Models\Game;
use Illuminate\View\ViewException;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

test('lineup submission renders team a button and modal', function (): void {
    $game = Game::factory()->create();

    Livewire::test(LineupSubmission::class, ['team' => TeamAB::TeamA, 'gameId' => $game->getKey()])
        ->assertSee('Submit Lineup')
        ->assertSee('Team A Lineup')
        ->assertSeeHtml('submit-lineup-team_a')
        ->assertSeeHtml('name="lineup[1]"')
        ->assertSeeHtml('name="lineup[6]"')
        ->assertSee('Submit');
});

test('lineup submission renders team b button and modal', function (): void {
    $game = Game::factory()->create();

    Livewire::test(LineupSubmission::class, ['team' => TeamAB::TeamB, 'gameId' => $game->getKey()])
        ->assertSee('Submit Lineup')
        ->assertSee('Team B Lineup')
        ->assertSeeHtml('submit-lineup-team_b')
        ->assertSeeHtml('name="lineup[1]"')
        ->assertSeeHtml('name="lineup[6]"')
        ->assertSee('Submit');
});

test('lineup submission rejects unsupported team value', function (): void {
    $game = Game::factory()->create();

    expect(fn (): Testable => Livewire::test(LineupSubmission::class, ['team' => 'invalid', 'gameId' => $game->getKey()]))
        ->toThrow(ViewException::class);
});

test('lineup submission accepts submit action', function (): void {
    $game = Game::factory()->create();

    Livewire::test(LineupSubmission::class, ['team' => TeamAB::TeamA, 'gameId' => $game->getKey()])
        ->set('lineup.1', '1')
        ->set('lineup.2', '2')
        ->set('lineup.3', '3')
        ->set('lineup.4', '4')
        ->set('lineup.5', '5')
        ->set('lineup.6', '6')
        ->call('submit')
        ->assertHasNoErrors();
});

test('lineup submission is aware of the injected game context', function (): void {
    $game = Game::factory()->create();

    Livewire::test(LineupSubmission::class, [
        'team' => TeamAB::TeamA,
        'gameId' => $game->getKey(),
        'gameState' => [
            'set_number' => 2,
            'serving_team' => TeamAB::TeamB->value,
        ],
    ])
        ->assertSet('gameId', $game->getKey())
        ->assertSet('gameState.set_number', 2)
        ->assertSet('gameState.serving_team', TeamAB::TeamB->value);
});
