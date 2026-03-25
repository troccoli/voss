<?php

declare(strict_types=1);

use App\Data\GameState\GameState;
use App\Enums\TeamAB;
use App\Enums\TeamSide;
use App\Livewire\Court;
use App\Models\Game;
use App\Models\Player;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('court shows team a on the left and team b on the right before toss', function (): void {
    $game = gameWithNumberedRosters();

    Livewire::test(Court::class, ['gameId' => $game->getKey(), 'gameState' => gameState(['set_number' => 0])])
        ->assertSeeInOrder([
            'Team A',
            'Anderson 3',
            'Zephyr 12',
            'Team B',
            '2 Baker',
            '9 Young',
        ])
        ->assertDontSee('1 Libero')
        ->assertDontSee('20 Keeper')
        ->assertDontSee('Anna')
        ->assertDontSee('Beth')
        ->assertDontSee('Dora')
        ->assertDontSee('Etta');
});

test('court swaps team sides in sets two three and four', function (): void {
    $game = gameWithNumberedRosters();
    $game->recordToss(TeamSide::Home, TeamAB::TeamA);

    Livewire::test(Court::class, ['gameId' => $game->getKey(), 'gameState' => gameState(['set_number' => 2])])
        ->assertSeeInOrder([
            'Team B',
            'Baker 2',
            'Young 9',
            'Team A',
            '3 Anderson',
            '12 Zephyr',
        ]);

    Livewire::test(Court::class, ['gameId' => $game->getKey(), 'gameState' => gameState(['set_number' => 3])])
        ->assertSeeInOrder([
            'Team A',
            'Anderson 3',
            'Zephyr 12',
            'Team B',
            '2 Baker',
            '9 Young',
        ]);

    Livewire::test(Court::class, ['gameId' => $game->getKey(), 'gameState' => gameState(['set_number' => 4])])
        ->assertSeeInOrder([
            'Team B',
            'Baker 2',
            'Young 9',
            'Team A',
            '3 Anderson',
            '12 Zephyr',
        ]);
});

test('court alternates left and right rosters from set one to set four', function (): void {
    $game = gameWithNumberedRosters();
    $game->recordToss(TeamSide::Home, TeamAB::TeamA);

    $setExpectations = [
        1 => [
            'Team A',
            'Anderson 3',
            'Zephyr 12',
            'Team B',
            '2 Baker',
            '9 Young',
        ],
        2 => [
            'Team B',
            'Baker 2',
            'Young 9',
            'Team A',
            '3 Anderson',
            '12 Zephyr',
        ],
        3 => [
            'Team A',
            'Anderson 3',
            'Zephyr 12',
            'Team B',
            '2 Baker',
            '9 Young',
        ],
        4 => [
            'Team B',
            'Baker 2',
            'Young 9',
            'Team A',
            '3 Anderson',
            '12 Zephyr',
        ],
    ];

    foreach ($setExpectations as $setNumber => $expectedVisibleText) {
        Livewire::test(Court::class, ['gameId' => $game->getKey(), 'gameState' => gameState(['set_number' => $setNumber])])
            ->assertSeeInOrder($expectedVisibleText);
    }
});

test('court uses toss assignment for first and fifth set side labels', function (): void {
    $game = gameWithNumberedRosters();
    $game->recordToss(TeamSide::Away, TeamAB::TeamA);

    Livewire::test(Court::class, ['gameId' => $game->getKey(), 'gameState' => gameState(['set_number' => 1])])
        ->assertSeeInOrder([
            'Team B',
            'Anderson 3',
            'Zephyr 12',
            'Team A',
            '2 Baker',
            '9 Young',
        ]);

    Livewire::test(Court::class, ['gameId' => $game->getKey(), 'gameState' => gameState(['set_number' => 5])])
        ->assertSeeInOrder([
            'Team B',
            'Anderson 3',
            'Zephyr 12',
            'Team A',
            '2 Baker',
            '9 Young',
        ]);
});

test('court shows lineup position one as bottom left for the left side and top right for the right side', function (): void {
    $game = gameWithNumberedRosters();
    $game->recordToss(TeamSide::Home, TeamAB::TeamA);

    Livewire::test(Court::class, [
        'gameId' => $game->getKey(),
        'gameState' => gameState([
            'set_number' => 1,
            'rotation_team_a' => [1 => 12],
            'rotation_team_b' => [1 => 9],
        ]),
    ])
        ->assertSeeHtml('data-court-marker="left-team_a-1"')
        ->assertSeeHtml('data-court-marker="right-team_b-1"')
        ->assertSeeHtml('left-[12%] bottom-[14%]')
        ->assertSeeHtml('right-[12%] top-[14%]');
});

test('court position one anchors follow the side after team swap', function (): void {
    $game = gameWithNumberedRosters();
    $game->recordToss(TeamSide::Home, TeamAB::TeamA);

    Livewire::test(Court::class, [
        'gameId' => $game->getKey(),
        'gameState' => gameState([
            'set_number' => 2,
            'rotation_team_a' => [1 => 12],
            'rotation_team_b' => [1 => 9],
        ]),
    ])
        ->assertSeeHtml('data-court-marker="left-team_b-1"')
        ->assertSeeHtml('data-court-marker="right-team_a-1"')
        ->assertSeeHtml('left-[12%] bottom-[14%]')
        ->assertSeeHtml('right-[12%] top-[14%]');
});

test('court shows serving team position one outside the court on the left side', function (): void {
    $game = gameWithNumberedRosters();
    $game->recordToss(TeamSide::Home, TeamAB::TeamA);

    Livewire::test(Court::class, [
        'gameId' => $game->getKey(),
        'gameState' => gameState([
            'set_number' => 1,
            'serving_team' => TeamAB::TeamA->value,
            'rotation_team_a' => [1 => 12],
            'rotation_team_b' => [1 => 9],
        ]),
    ])
        ->assertSeeHtml('data-court-marker="left-team_a-1"')
        ->assertSeeHtml('data-court-serving-player="1"')
        ->assertSeeHtml('-left-10 bottom-[14%]')
        ->assertSeeHtml('data-court-marker="right-team_b-1"')
        ->assertSeeHtml('right-[12%] top-[14%]');
});

test('court shows serving team position one outside the court after side swap', function (): void {
    $game = gameWithNumberedRosters();
    $game->recordToss(TeamSide::Home, TeamAB::TeamA);

    Livewire::test(Court::class, [
        'gameId' => $game->getKey(),
        'gameState' => gameState([
            'set_number' => 2,
            'serving_team' => TeamAB::TeamA->value,
            'rotation_team_a' => [1 => 12],
            'rotation_team_b' => [1 => 9],
        ]),
    ])
        ->assertSeeHtml('data-court-marker="right-team_a-1"')
        ->assertSeeHtml('data-court-serving-player="1"')
        ->assertSeeHtml('-right-10 top-[14%]')
        ->assertSeeHtml('data-court-marker="left-team_b-1"')
        ->assertSeeHtml('left-[12%] bottom-[14%]');
});

function gameWithNumberedRosters(): Game
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

/**
 * @param  array<string, mixed>  $attributes
 */
function gameState(array $attributes): GameState
{
    return GameState::fromAttributes($attributes);
}
