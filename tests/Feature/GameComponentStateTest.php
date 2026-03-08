<?php

declare(strict_types=1);

use App\Enums\TeamAB;
use App\Enums\TeamSide;
use App\Livewire\Game;
use App\Models\Game as GameModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('game component hydrates state from the passed game', function (): void {
    $game = GameModel::factory()->create();
    $game->recordToss(TeamSide::Away, TeamAB::TeamB);

    Livewire::test(Game::class, ['game' => $game])
        ->assertSet('gameId', $game->getKey())
        ->assertSet('gameState.serving_team', TeamAB::TeamB->value)
        ->assertSet('gameState.set_number', 0)
        ->assertSet('gameState.rotation_team_a', [])
        ->assertSet('gameState.rotation_team_b', []);
});

test('game component uses the passed game id instead of the latest game', function (): void {
    $targetGame = GameModel::factory()->create();
    $targetGame->recordToss(TeamSide::Home, TeamAB::TeamA);

    $latestGame = GameModel::factory()->create();
    $latestGame->recordToss(TeamSide::Away, TeamAB::TeamB);

    Livewire::test(Game::class, ['game' => $targetGame])
        ->assertSet('gameId', $targetGame->getKey())
        ->assertSet('gameState.serving_team', TeamAB::TeamA->value);
});
