<?php

declare(strict_types=1);

use App\Data\GameState\GameState;
use App\Enums\TeamAB;
use App\Enums\TeamSide;
use App\Livewire\Game;
use App\Models\Game as GameModel;
use App\Models\Player;
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

    Livewire::test(Game::class, ['game' => $game])
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
    if ($game->players()->count() === 0) {
        $homePlayers = Player::factory()->for($game->homeTeam)->count(6)->create();
        foreach ($homePlayers as $index => $player) {
            $game->addPlayer($player, number: $index + 1);
        }

        $awayPlayers = Player::factory()->for($game->awayTeam)->count(6)->create();
        foreach ($awayPlayers as $index => $player) {
            $game->addPlayer($player, number: $index + 11);
        }
    }

    $game->recordLineup($set, TeamAB::TeamA, lineupPositions(1));
    $game->recordLineup($set, TeamAB::TeamB, lineupPositions(11));
}

/**
 * @return array<int, int>
 */
function lineupPositions(int $start): array
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
