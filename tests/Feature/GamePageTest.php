<?php

declare(strict_types=1);

use App\Enums\TeamAB;
use App\Enums\TeamSide;
use App\Livewire\Game;
use App\Models\Game as GameModel;
use App\Models\Player;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('game.between_sets_duration', 0);
});

test('the game page returns a successful response and renders the fixed canvas with the court', function (): void {
    $game = GameModel::factory()->create();

    $response = $this->get(route('game', ['game' => $game]));

    $response->assertSuccessful()
        ->assertSee('id="game-canvas"', false)
        ->assertSee('bg-sky-100')
        ->assertSee('id="volleyball-court"', false)
        ->assertSee('Submit rosters')
        ->assertDontSee('Submit Toss Result')
        ->assertDontSee('data-scoreboard', false)
        ->assertDontSee('Submit Lineup');
});

test('the game livewire component renders the court component', function (): void {
    $game = GameModel::factory()->create();

    Livewire::test(Game::class, ['game' => $game])
        ->assertSeeHtml('id="game-canvas"')
        ->assertSeeHtml('id="volleyball-court"')
        ->assertSee('Submit rosters')
        ->assertDontSeeHtml('data-scoreboard');
});

test('the game page renders the start set button when both lineups are submitted for the upcoming set', function (): void {
    $homeTeam = Team::factory()->create();
    $awayTeam = Team::factory()->create();
    $game = GameModel::factory()->betweenTeams($homeTeam, $awayTeam)->create();

    $homePlayers = Player::factory()->for($homeTeam)->count(6)->create();
    foreach ($homePlayers as $index => $player) {
        $game->addPlayer($player, number: $index + 1);
    }

    $awayPlayers = Player::factory()->for($awayTeam)->count(6)->create();
    foreach ($awayPlayers as $index => $player) {
        $game->addPlayer($player, number: $index + 11);
    }

    $game->recordToss(TeamSide::Home, TeamAB::TeamA);
    $game->recordLineup(1, TeamAB::TeamA, [1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5, 6 => 6]);
    $game->recordLineup(1, TeamAB::TeamB, [1 => 11, 2 => 12, 3 => 13, 4 => 14, 5 => 15, 6 => 16]);

    $response = $this->get(route('game', ['game' => $game]));

    $response->assertSuccessful()
        ->assertSee('Start Game');
});

test('the game component prompts for the fifth set side change at 8 points and swaps sides when dismissed', function (): void {
    $homeTeam = Team::factory()->create();
    $awayTeam = Team::factory()->create();
    $game = GameModel::factory()->betweenTeams($homeTeam, $awayTeam)->create();

    $homePlayers = Player::factory()->for($homeTeam)->count(6)->create();
    foreach ($homePlayers as $index => $player) {
        $game->addPlayer($player, number: $index + 1);
    }

    $awayPlayers = Player::factory()->for($awayTeam)->count(6)->create();
    foreach ($awayPlayers as $index => $player) {
        $game->addPlayer($player, number: $index + 11);
    }

    $game->recordToss(TeamSide::Home, TeamAB::TeamA);

    foreach ([TeamAB::TeamA, TeamAB::TeamB, TeamAB::TeamA, TeamAB::TeamB] as $winner) {
        $set = $game->stateAt()->setNumber + 1;
        $game->recordLineup($set, TeamAB::TeamA, [1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5, 6 => 6]);
        $game->recordLineup($set, TeamAB::TeamB, [1 => 11, 2 => 12, 3 => 13, 4 => 14, 5 => 15, 6 => 16]);
        $game->recordSetStarted();

        for ($index = 0; $index < 25; $index++) {
            $game->recordRallyWinner($winner);
        }
    }

    $game->recordToss(TeamSide::Home, TeamAB::TeamA, TeamAB::TeamA);
    $game->recordLineup(5, TeamAB::TeamA, [1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5, 6 => 6]);
    $game->recordLineup(5, TeamAB::TeamB, [1 => 11, 2 => 12, 3 => 13, 4 => 14, 5 => 15, 6 => 16]);
    $game->recordSetStarted();

    for ($index = 0; $index < 8; $index++) {
        $game->recordRallyWinner(TeamAB::TeamA);
    }

    Livewire::test(Game::class, ['game' => $game->fresh()])
        ->assertSet('showFifthSetSideChangePrompt', true)
        ->assertSee('Teams to change court')
        ->assertSeeHtml('x-on:keydown.escape.window.capture.prevent.stop=""')
        ->assertSeeHtml('x-on:cancel.prevent.stop=""')
        ->call('acknowledgeFifthSetSideChange')
        ->assertDispatched('game-event-recorded')
        ->assertSet('showFifthSetSideChangePrompt', false);

    expect($game->fresh()->stateAt()->fifthSetLeftTeam)->toBe(TeamAB::TeamB)
        ->and($game->fresh()->stateAt()->servingTeam)->toBe(TeamAB::TeamA);
});
