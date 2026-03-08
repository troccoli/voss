<?php

declare(strict_types=1);

use App\Livewire\Game;
use App\Models\Game as GameModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('the game page returns a successful response and renders the fixed canvas with the court', function (): void {
    $game = GameModel::factory()->create();

    $response = $this->get(route('game', ['game' => $game]));

    $response->assertSuccessful()
        ->assertSee('id="game-canvas"', false)
        ->assertSee('bg-sky-100')
        ->assertSee('id="volleyball-court"', false)
        ->assertSee('Submit Toss Result')
        ->assertSee('Submit Team A Lineup')
        ->assertSee('Submit Team B Lineup');
});

test('the game livewire component renders the court component', function (): void {
    $game = GameModel::factory()->create();

    Livewire::test(Game::class, ['game' => $game])
        ->assertSeeHtml('id="game-canvas"')
        ->assertSeeHtml('id="volleyball-court"');
});
