<?php

declare(strict_types=1);

use App\Enums\GameEventType;
use App\Enums\TeamAB;
use App\Enums\TeamSide;
use App\Livewire\StartSetSubmission;
use App\Models\Game;
use App\Models\Player;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('start set button is visible only when both team lineups are submitted for the upcoming set', function (): void {
    $game = gameReadyToStartSet();

    $game->recordLineup(1, TeamAB::TeamA, lineupPositionsForNumbers(1));

    Livewire::test(StartSetSubmission::class, ['gameId' => $game->getKey(), 'gameState' => $game->stateAt()])
        ->assertDontSee('Start Set 1');

    $game->recordLineup(1, TeamAB::TeamB, lineupPositionsForNumbers(11));

    Livewire::test(StartSetSubmission::class, ['gameId' => $game->getKey(), 'gameState' => $game->stateAt()])
        ->assertSee('Start Set 1');
});

test('start set button records set started event and dispatches refresh event', function (): void {
    $game = gameReadyToStartSet();
    $game->recordLineup(1, TeamAB::TeamA, lineupPositionsForNumbers(1));
    $game->recordLineup(1, TeamAB::TeamB, lineupPositionsForNumbers(11));

    Livewire::test(StartSetSubmission::class, ['gameId' => $game->getKey(), 'gameState' => $game->stateAt()])
        ->assertSee('Start Set 1')
        ->call('startSet')
        ->assertHasNoErrors()
        ->assertDispatched('game-event-recorded')
        ->assertDontSee('Start Set 1');

    $freshGame = $game->fresh();
    $latestEvent = $freshGame->events->last();

    expect($latestEvent)->not->toBeNull()
        ->and($latestEvent->type)->toBe(GameEventType::SetStarted)
        ->and($freshGame->stateAt()->setNumber)->toBe(1)
        ->and($freshGame->stateAt()->setInProgress)->toBeTrue();
});

test('start set button label uses the next dynamic set number', function (): void {
    $game = gameReadyToStartSet();
    $game->recordLineup(1, TeamAB::TeamA, lineupPositionsForNumbers(1));
    $game->recordLineup(1, TeamAB::TeamB, lineupPositionsForNumbers(11));
    $game->recordSetStarted();

    for ($i = 0; $i < 25; $i++) {
        $game->recordRallyWinner(TeamAB::TeamA);
    }

    $game->recordLineup(2, TeamAB::TeamA, lineupPositionsForNumbers(1));
    $game->recordLineup(2, TeamAB::TeamB, lineupPositionsForNumbers(11));

    Livewire::test(StartSetSubmission::class, ['gameId' => $game->getKey(), 'gameState' => $game->stateAt()])
        ->assertSee('Start Set 2');
});

/**
 * @return array<int, int>
 */
function lineupPositionsForNumbers(int $start): array
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

function gameReadyToStartSet(): Game
{
    $homeTeam = Team::factory()->create();
    $awayTeam = Team::factory()->create();
    $game = Game::factory()->betweenTeams($homeTeam, $awayTeam)->create();

    $homePlayers = Player::factory()->for($homeTeam)->count(6)->create();
    foreach ($homePlayers as $index => $player) {
        $game->addPlayer($player, number: $index + 1);
    }

    $awayPlayers = Player::factory()->for($awayTeam)->count(6)->create();
    foreach ($awayPlayers as $index => $player) {
        $game->addPlayer($player, number: $index + 11);
    }

    $game->recordToss(TeamSide::Home, TeamAB::TeamA);

    return $game;
}
