<?php

declare(strict_types=1);

use App\Enums\TeamAB;
use App\Enums\TeamSide;
use App\Livewire\Game;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('game.between_sets_duration', 0);
});

test('the game page redirects to setup until the static match setup is complete', function (): void {
    createCurrentMatch();

    $this->get(route('game'))
        ->assertRedirect(route('match.setup'));
});

test('the game livewire component renders the court component', function (): void {
    makeReadyCurrentMatch();

    Livewire::test(Game::class)
        ->assertSeeHtml('id="game-canvas"')
        ->assertSeeHtml('id="volleyball-court"')
        ->assertSee('Submit Toss Result')
        ->assertDontSeeHtml('data-scoreboard');
});

test('the game page renders the start set button when both lineups are submitted for the upcoming set', function (): void {
    $game = makeReadyCurrentMatch();
    $game->recordToss(TeamSide::Home, TeamAB::TeamA);
    $game->recordLineup(1, TeamAB::TeamA, standardLineup());
    $game->recordLineup(1, TeamAB::TeamB, standardLineup(11));

    $response = $this->get(route('game'));

    $response->assertSuccessful()
        ->assertSee('Start Game');
});

test('the game component prompts for the fifth set side change at 8 points and swaps sides when dismissed', function (): void {
    $game = makeReadyCurrentMatch();
    $game->recordToss(TeamSide::Home, TeamAB::TeamA);

    foreach ([TeamAB::TeamA, TeamAB::TeamB, TeamAB::TeamA, TeamAB::TeamB] as $winner) {
        $set = $game->stateAt()->setNumber + 1;
        $game->recordLineup($set, TeamAB::TeamA, standardLineup());
        $game->recordLineup($set, TeamAB::TeamB, standardLineup(11));
        $game->recordSetStarted();

        for ($index = 0; $index < 25; $index++) {
            $game->recordRallyWinner($winner);
        }
    }

    $game->recordToss(TeamSide::Home, TeamAB::TeamA, TeamAB::TeamA);
    $game->recordLineup(5, TeamAB::TeamA, standardLineup());
    $game->recordLineup(5, TeamAB::TeamB, standardLineup(11));
    $game->recordSetStarted();

    for ($index = 0; $index < 8; $index++) {
        $game->recordRallyWinner(TeamAB::TeamA);
    }

    Livewire::test(Game::class)
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
