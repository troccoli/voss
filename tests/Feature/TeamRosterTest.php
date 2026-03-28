<?php

declare(strict_types=1);

use App\Data\GameState\GameState;
use App\Enums\TeamAB;
use App\Enums\TeamSide;
use App\Livewire\TeamRoster;
use App\Models\Game;
use App\Models\Player;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('team roster shows fallback text when there are no players', function (): void {
    $homeTeam = Team::factory()->create();
    $awayTeam = Team::factory()->create();
    $game = Game::factory()->betweenTeams($homeTeam, $awayTeam)->create();

    Livewire::test(TeamRoster::class, [
        'gameId' => $game->getKey(),
        'team' => TeamAB::TeamA,
        'leftSide' => true,
    ])
        ->assertDontSee('Team A')
        ->assertDontSee('Team B')
        ->assertSee('No players available.');
});

test('team roster renders team a on the left with number markers only', function (): void {
    $game = gameWithNumberedRostersForTeamRoster();

    Livewire::test(TeamRoster::class, [
        'gameId' => $game->getKey(),
        'team' => TeamAB::TeamA,
        'leftSide' => true,
    ])->assertSeeInOrder([
        '3',
        '12',
    ]);
});

test('team roster renders team b on the right with number markers only', function (): void {
    $game = gameWithNumberedRostersForTeamRoster();

    Livewire::test(TeamRoster::class, [
        'gameId' => $game->getKey(),
        'team' => TeamAB::TeamB,
        'leftSide' => false,
    ])->assertSeeInOrder([
        '2',
        '9',
    ]);
});

test('team roster resolves team a players from toss assignment', function (): void {
    $game = gameWithNumberedRostersForTeamRoster();
    $game->recordToss(TeamSide::Away, TeamAB::TeamA);

    Livewire::test(TeamRoster::class, [
        'gameId' => $game->getKey(),
        'team' => TeamAB::TeamA,
        'leftSide' => true,
    ])->assertSeeInOrder([
        '2',
        '9',
    ]);
});

test('team roster resolves toss assignment immediately after toss submission', function (): void {
    $game = gameWithNumberedRostersForTeamRoster();
    $game->recordToss(TeamSide::Away, TeamAB::TeamA);

    Livewire::test(TeamRoster::class, [
        'gameId' => $game->getKey(),
        'team' => TeamAB::TeamA,
        'leftSide' => true,
    ])->assertSeeInOrder([
        '2',
        '9',
    ])->assertDontSeeHtml('data-team-roster-number="3"');
});

test('team roster hides on-court players when lineup rotation exists', function (): void {
    $game = gameWithNumberedRostersForTeamRoster();

    Livewire::test(TeamRoster::class, [
        'gameId' => $game->getKey(),
        'team' => TeamAB::TeamA,
        'leftSide' => true,
        'gameState' => GameState::fromAttributes([
            'rotation_team_a' => [
                1 => 3,
            ],
        ]),
    ])
        ->assertDontSeeHtml('data-team-roster-number="3"')
        ->assertSeeHtml('data-team-roster-number="12"');
});

test('team roster can render after a second Livewire request', function (): void {
    $game = gameWithNumberedRostersForTeamRoster();

    Livewire::test(TeamRoster::class, [
        'gameId' => $game->getKey(),
        'team' => TeamAB::TeamA,
        'leftSide' => true,
    ])
        ->assertSeeHtml('data-team-roster-number="3"')
        ->call('$refresh')
        ->assertSeeHtml('data-team-roster-number="3"');
});

function gameWithNumberedRostersForTeamRoster(): Game
{
    $homeTeam = Team::factory()->create();
    $awayTeam = Team::factory()->create();
    $game = Game::factory()->betweenTeams($homeTeam, $awayTeam)->create();

    $homePlayerOne = Player::factory()->for($homeTeam)->named('Anna', 'Zephyr')->create();
    $homePlayerTwo = Player::factory()->for($homeTeam)->named('Beth', 'Anderson')->create();
    $homeLibero = Player::factory()->for($homeTeam)->named('Cara', 'Libero')->create();

    $awayPlayerOne = Player::factory()->for($awayTeam)->named('Dora', 'Young')->create();
    $awayPlayerTwo = Player::factory()->for($awayTeam)->named('Etta', 'Baker')->create();
    $awayLibero = Player::factory()->for($awayTeam)->named('Faye', 'Keeper')->create();

    $game->addPlayer($homePlayerOne, number: 12);
    $game->addPlayer($homePlayerTwo, number: 3);
    $game->addPlayer($homeLibero, number: 1, isLibero: true);
    $game->addPlayer($awayPlayerOne, number: 9);
    $game->addPlayer($awayPlayerTwo, number: 2);
    $game->addPlayer($awayLibero, number: 20, isLibero: true);

    return $game;
}
