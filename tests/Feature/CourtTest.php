<?php

declare(strict_types=1);

use App\Data\GameState\GameState;
use App\Enums\GameEventType;
use App\Enums\TeamAB;
use App\Enums\TeamSide;
use App\Events\Payloads\RallyEndedPayload;
use App\Livewire\Court;
use App\Models\Game;
use App\Models\Player;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('court does not show player lists before toss is submitted', function (): void {
    $game = gameWithNumberedRosters();

    Livewire::test(Court::class, ['gameId' => $game->getKey(), 'gameState' => gameState(['set_number' => 0])])
        ->assertDontSee('Submit Lineup')
        ->assertDontSee('Anderson 3')
        ->assertDontSee('Zephyr 12')
        ->assertDontSee('2 Baker')
        ->assertDontSee('9 Young')
        ->assertDontSee('1 Libero')
        ->assertDontSee('20 Keeper')
        ->assertDontSee('Anna')
        ->assertDontSee('Beth')
        ->assertDontSee('Dora')
        ->assertDontSee('Etta');
});

test('court shows player lists after toss is submitted', function (): void {
    $game = gameWithNumberedRosters();
    $game->recordToss(TeamSide::Home, TeamAB::TeamA);

    Livewire::test(Court::class, ['gameId' => $game->getKey(), 'gameState' => gameState(['set_number' => 1])])
        ->assertSeeInOrder([
            'Team A',
            'Anderson 3',
            'Zephyr 12',
            'Team B',
            '2 Baker',
            '9 Young',
        ])
        ->assertSee('Submit Lineup')
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

    Livewire::test(Court::class, ['gameId' => $game->getKey(), 'gameState' => gameState(['set_number' => 2, 'sets_won_team_a' => 1])])
        ->assertSeeInOrder([
            'Team B',
            'Baker 2',
            'Young 9',
            'Team A',
            '3 Anderson',
            '12 Zephyr',
        ]);

    Livewire::test(Court::class, ['gameId' => $game->getKey(), 'gameState' => gameState(['set_number' => 3, 'sets_won_team_a' => 1, 'sets_won_team_b' => 1])])
        ->assertSeeInOrder([
            'Team A',
            'Anderson 3',
            'Zephyr 12',
            'Team B',
            '2 Baker',
            '9 Young',
        ]);

    Livewire::test(Court::class, ['gameId' => $game->getKey(), 'gameState' => gameState(['set_number' => 4, 'sets_won_team_a' => 2, 'sets_won_team_b' => 1])])
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
        1 => ['set_number' => 1, 'sets_won_team_a' => 0, 'sets_won_team_b' => 0, 'expected' => [
            'Team A',
            'Anderson 3',
            'Zephyr 12',
            'Team B',
            '2 Baker',
            '9 Young',
        ]],
        2 => ['set_number' => 2, 'sets_won_team_a' => 1, 'sets_won_team_b' => 0, 'expected' => [
            'Team B',
            'Baker 2',
            'Young 9',
            'Team A',
            '3 Anderson',
            '12 Zephyr',
        ]],
        3 => ['set_number' => 3, 'sets_won_team_a' => 1, 'sets_won_team_b' => 1, 'expected' => [
            'Team A',
            'Anderson 3',
            'Zephyr 12',
            'Team B',
            '2 Baker',
            '9 Young',
        ]],
        4 => ['set_number' => 4, 'sets_won_team_a' => 2, 'sets_won_team_b' => 1, 'expected' => [
            'Team B',
            'Baker 2',
            'Young 9',
            'Team A',
            '3 Anderson',
            '12 Zephyr',
        ]],
    ];

    foreach ($setExpectations as $state) {
        Livewire::test(Court::class, [
            'gameId' => $game->getKey(),
            'gameState' => gameState([
                'set_number' => $state['set_number'],
                'sets_won_team_a' => $state['sets_won_team_a'],
                'sets_won_team_b' => $state['sets_won_team_b'],
            ]),
        ])->assertSeeInOrder($state['expected']);
    }
});

test('court keeps team a on the left in first and fifth sets regardless of toss side assignment', function (): void {
    $game = gameWithNumberedRosters();
    $game->recordToss(TeamSide::Away, TeamAB::TeamA);

    Livewire::test(Court::class, ['gameId' => $game->getKey(), 'gameState' => gameState(['set_number' => 1])])
        ->assertSeeInOrder([
            'Team A',
            'Baker 2',
            'Young 9',
            'Team B',
            '3 Anderson',
            '12 Zephyr',
        ]);

    Livewire::test(Court::class, ['gameId' => $game->getKey(), 'gameState' => gameState(['set_number' => 5, 'sets_won_team_a' => 2, 'sets_won_team_b' => 2])])
        ->assertSeeInOrder([
            'Team A',
            'Baker 2',
            'Young 9',
            'Team B',
            '3 Anderson',
            '12 Zephyr',
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
            'sets_won_team_a' => 1,
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
            'sets_won_team_a' => 1,
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

test('court swaps sides as soon as a set ends before the next set starts', function (): void {
    $game = gameWithNumberedRosters();
    $game->recordToss(TeamSide::Home, TeamAB::TeamA);

    Livewire::test(Court::class, [
        'gameId' => $game->getKey(),
        'gameState' => gameState([
            'set_number' => 1,
            'sets_won_team_a' => 1,
            'set_in_progress' => false,
        ]),
    ])
        ->assertSeeInOrder([
            'Team B',
            'Baker 2',
            'Young 9',
            'Team A',
            '3 Anderson',
            '12 Zephyr',
        ]);
});

test('court shows rally winner buttons only while a set is in progress', function (): void {
    $game = Game::factory()->create();

    Livewire::test(Court::class, [
        'gameId' => $game->getKey(),
        'gameState' => gameState(['set_number' => 1, 'set_in_progress' => false]),
    ])
        ->assertDontSee('Winner')
        ->assertDontSeeHtml('data-rally-winner-button="team_a"')
        ->assertDontSeeHtml('data-rally-winner-button="team_b"');

    Livewire::test(Court::class, [
        'gameId' => $game->getKey(),
        'gameState' => gameState(['set_number' => 1, 'set_in_progress' => true]),
    ])
        ->assertSee('Winner')
        ->assertSeeHtml('data-rally-winner-button="team_a"')
        ->assertSeeHtml('data-rally-winner-button="team_b"');
});

test('court records rally winner for the selected team and dispatches a refresh event', function (): void {
    $game = gameWithStartedSet();

    Livewire::test(Court::class, [
        'gameId' => $game->getKey(),
        'gameState' => $game->stateAt(),
    ])
        ->assertSee('Winner')
        ->assertSeeHtml('data-rally-winner-button="team_a"')
        ->assertSeeHtml('data-rally-winner-button="team_b"')
        ->call('recordRallyWinner', TeamAB::TeamA->value)
        ->assertHasNoErrors()
        ->assertDispatched('game-event-recorded');

    $latestEvent = $game->fresh()->events->last();

    expect($latestEvent)->not->toBeNull()
        ->and($latestEvent->type)->toBe(GameEventType::RallyEnded)
        ->and($latestEvent->payload)->toBeInstanceOf(RallyEndedPayload::class)
        ->and($latestEvent->payload->team)->toBe(TeamAB::TeamA);
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

function gameWithStartedSet(): Game
{
    $game = Game::factory()->create();
    $game->recordToss(TeamSide::Home, TeamAB::TeamA);
    $game->recordSetStarted();

    return $game;
}

/**
 * @param  array<string, mixed>  $attributes
 */
function gameState(array $attributes): GameState
{
    return GameState::fromAttributes($attributes);
}
