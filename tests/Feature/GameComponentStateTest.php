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

beforeEach(function (): void {
    config()->set('game.between_sets_duration', 0);
});

test('game component hydrates state from the current singleton match', function (): void {
    $game = makeReadyCurrentMatch();
    $game->recordToss(TeamSide::Away, TeamAB::TeamB);

    Livewire::test(Game::class)
        ->assertSet('gameState', fn (GameState $gameState): bool => $gameState->servingTeam === TeamAB::TeamB
            && $gameState->setNumber === 0
            && $gameState->rotationTeamA === []
            && $gameState->rotationTeamB === []);
});

test('game component renders sets and current set points for both teams', function (): void {
    $game = makeReadyCurrentMatch();
    $game->recordToss(TeamSide::Home, TeamAB::TeamA);
    ensureLineupsForSet($game, 1);
    $game->recordSetStarted();

    for ($index = 0; $index < 25; $index++) {
        $game->recordRallyWinner(TeamAB::TeamA);
    }

    ensureLineupsForSet($game, 2);
    $game->recordSetStarted();

    for ($index = 0; $index < 7; $index++) {
        $game->recordRallyWinner(TeamAB::TeamA);
    }

    for ($index = 0; $index < 3; $index++) {
        $game->recordRallyWinner(TeamAB::TeamB);
    }

    Livewire::test(Game::class)
        ->assertSet('gameState', fn (GameState $gameState): bool => $gameState->setsWonTeamA === 1
            && $gameState->setsWonTeamB === 0
            && $gameState->scoreTeamA === 7
            && $gameState->scoreTeamB === 3)
        ->assertSee('Sets')
        ->assertSee('Points')
        ->assertSeeInOrder([
            'Sets',
            '1',
            '0',
            'Points',
            '7',
            '3',
        ]);
});

function ensureLineupsForSet(GameModel $game, int $set): void
{
    $game->recordLineup($set, TeamAB::TeamA, standardLineup());
    $game->recordLineup($set, TeamAB::TeamB, standardLineup(11));
}
