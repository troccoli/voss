<?php

declare(strict_types=1);

use App\Data\GameState\GameState;
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
        ->assertSet('gameState', fn (GameState $gameState): bool => $gameState->servingTeam === TeamAB::TeamB
            && $gameState->setNumber === 0
            && $gameState->rotationTeamA === []
            && $gameState->rotationTeamB === []);
});

test('game component uses the passed game id instead of the latest game', function (): void {
    $targetGame = GameModel::factory()->create();
    $targetGame->recordToss(TeamSide::Home, TeamAB::TeamA);

    $latestGame = GameModel::factory()->create();
    $latestGame->recordToss(TeamSide::Away, TeamAB::TeamB);

    Livewire::test(Game::class, ['game' => $targetGame])
        ->assertSet('gameId', $targetGame->getKey())
        ->assertSet('gameState', fn (GameState $gameState): bool => $gameState->servingTeam === TeamAB::TeamA);
});

test('game component renders sets and current set points for both teams', function (): void {
    $game = GameModel::factory()->create();
    $game->recordToss(TeamSide::Home, TeamAB::TeamA);
    $game->recordSetStarted();

    for ($index = 0; $index < 25; $index++) {
        $game->recordRallyWinner(TeamAB::TeamA);
    }

    $game->recordSetStarted();

    for ($index = 0; $index < 7; $index++) {
        $game->recordRallyWinner(TeamAB::TeamA);
    }

    for ($index = 0; $index < 3; $index++) {
        $game->recordRallyWinner(TeamAB::TeamB);
    }

    Livewire::test(Game::class, ['game' => $game])
        ->assertSet('gameState', fn (GameState $gameState): bool => $gameState->setsWonTeamA === 1
            && $gameState->setsWonTeamB === 0
            && $gameState->scoreTeamA === 7
            && $gameState->scoreTeamB === 3)
        ->assertSee('Sets')
        ->assertSee('Points')
        ->assertSeeHtml('data-scoreboard-sets-team-a')
        ->assertSeeHtml('data-scoreboard-sets-team-b')
        ->assertSeeHtml('data-scoreboard-points-team-a')
        ->assertSeeHtml('data-scoreboard-points-team-b')
        ->assertSeeInOrder([
            'Sets',
            '1',
            '0',
            'Points',
            '7',
            '3',
        ]);
});
