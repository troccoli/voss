<?php

declare(strict_types=1);

use App\Data\GameState\GameState;
use App\Enums\GameEventType;
use App\Enums\TeamAB;
use App\Enums\TeamSide;
use App\Events\Payloads\RallyEndedPayload;
use App\Livewire\Court;
use App\Livewire\RallyWinnerControls;
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
        ->assertDontSeeHtml('data-team-roster-number="3"')
        ->assertDontSeeHtml('data-team-roster-number="12"')
        ->assertDontSeeHtml('data-team-roster-number="2"')
        ->assertDontSeeHtml('data-team-roster-number="9"')
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

    Livewire::test(Court::class, ['gameId' => $game->getKey(), 'gameState' => gameStateWithSubmittedLineups(['set_number' => 1])])
        ->assertSeeInOrder([
            '3',
            '12',
            '2',
            '9',
        ])
        ->assertSee('Submit Lineup')
        ->assertDontSee('1 Libero')
        ->assertDontSee('20 Keeper')
        ->assertDontSee('Anna')
        ->assertDontSee('Beth')
        ->assertDontSee('Dora')
        ->assertDontSee('Etta');
});

test('court hides players currently on court from roster lists when lineup is present', function (): void {
    $game = gameWithNumberedRosters();
    $game->recordToss(TeamSide::Home, TeamAB::TeamA);

    Livewire::test(Court::class, [
        'gameId' => $game->getKey(),
        'gameState' => gameState([
            'set_number' => 1,
            'rotation_team_a' => [1 => 3],
            'rotation_team_b' => [1 => 2],
        ]),
    ])
        ->assertDontSeeHtml('data-team-roster-number="3"')
        ->assertSeeHtml('data-team-roster-number="12"')
        ->assertDontSeeHtml('data-team-roster-number="2"')
        ->assertSeeHtml('data-team-roster-number="9"');
});

test('court swaps team sides in sets two three and four', function (): void {
    $game = gameWithNumberedRosters();
    $game->recordToss(TeamSide::Home, TeamAB::TeamA);

    Livewire::test(Court::class, ['gameId' => $game->getKey(), 'gameState' => gameStateWithSubmittedLineups(['set_number' => 2, 'sets_won_team_a' => 1])])
        ->assertSeeInOrder([
            '2',
            '9',
            '3',
            '12',
        ]);

    Livewire::test(Court::class, ['gameId' => $game->getKey(), 'gameState' => gameStateWithSubmittedLineups(['set_number' => 3, 'sets_won_team_a' => 1, 'sets_won_team_b' => 1])])
        ->assertSeeInOrder([
            '3',
            '12',
            '2',
            '9',
        ]);

    Livewire::test(Court::class, ['gameId' => $game->getKey(), 'gameState' => gameStateWithSubmittedLineups(['set_number' => 4, 'sets_won_team_a' => 2, 'sets_won_team_b' => 1])])
        ->assertSeeInOrder([
            '2',
            '9',
            '3',
            '12',
        ]);
});

test('court alternates left and right rosters from set one to set four', function (): void {
    $game = gameWithNumberedRosters();
    $game->recordToss(TeamSide::Home, TeamAB::TeamA);

    $setExpectations = [
        1 => ['set_number' => 1, 'sets_won_team_a' => 0, 'sets_won_team_b' => 0, 'expected' => [
            '3',
            '12',
            '2',
            '9',
        ]],
        2 => ['set_number' => 2, 'sets_won_team_a' => 1, 'sets_won_team_b' => 0, 'expected' => [
            '2',
            '9',
            '3',
            '12',
        ]],
        3 => ['set_number' => 3, 'sets_won_team_a' => 1, 'sets_won_team_b' => 1, 'expected' => [
            '3',
            '12',
            '2',
            '9',
        ]],
        4 => ['set_number' => 4, 'sets_won_team_a' => 2, 'sets_won_team_b' => 1, 'expected' => [
            '2',
            '9',
            '3',
            '12',
        ]],
    ];

    foreach ($setExpectations as $state) {
        Livewire::test(Court::class, [
            'gameId' => $game->getKey(),
            'gameState' => gameStateWithSubmittedLineups([
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

    Livewire::test(Court::class, ['gameId' => $game->getKey(), 'gameState' => gameStateWithSubmittedLineups(['set_number' => 1])])
        ->assertSeeInOrder([
            '2',
            '9',
            '3',
            '12',
        ]);

    Livewire::test(Court::class, ['gameId' => $game->getKey(), 'gameState' => gameStateWithSubmittedLineups(['set_number' => 5, 'sets_won_team_a' => 2, 'sets_won_team_b' => 2])])
        ->assertSeeInOrder([
            '2',
            '9',
            '3',
            '12',
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

test('court keeps the serving marker on the left side after a set swap', function (): void {
    $game = gameWithStartedSet();

    for ($index = 0; $index < 25; $index++) {
        $game->recordRallyWinner(TeamAB::TeamA);
    }

    $game->recordLineup(2, TeamAB::TeamA, [1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5, 6 => 6]);
    $game->recordLineup(2, TeamAB::TeamB, [1 => 11, 2 => 12, 3 => 13, 4 => 14, 5 => 15, 6 => 16]);
    $game->recordSetStarted();

    Livewire::test(Court::class, [
        'gameId' => $game->getKey(),
        'gameState' => $game->stateAt(),
    ])
        ->assertSeeHtml('data-court-marker="left-team_b-1"')
        ->assertSeeHtml('data-court-serving-player="1"')
        ->assertSeeHtml('-left-10 bottom-[14%]')
        ->assertSeeHtml('data-court-marker="right-team_a-1"')
        ->assertSeeHtml('right-[12%] top-[14%]');
});

test('court keeps serving on the left side after swap even when team b won the previous set', function (): void {
    $game = gameWithStartedSet();

    for ($index = 0; $index < 25; $index++) {
        $game->recordRallyWinner(TeamAB::TeamB);
    }

    $game->recordLineup(2, TeamAB::TeamA, [1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5, 6 => 6]);
    $game->recordLineup(2, TeamAB::TeamB, [1 => 11, 2 => 12, 3 => 13, 4 => 14, 5 => 15, 6 => 16]);
    $game->recordSetStarted();

    Livewire::test(Court::class, [
        'gameId' => $game->getKey(),
        'gameState' => $game->stateAt(),
    ])
        ->assertSeeHtml('data-court-marker="left-team_b-1"')
        ->assertSeeHtml('data-court-serving-player="1"')
        ->assertSeeHtml('-left-10 bottom-[14%]')
        ->assertSeeHtml('data-court-marker="right-team_a-1"')
        ->assertSeeHtml('right-[12%] top-[14%]');
});

test('court swaps sides as soon as a set ends before the next set starts', function (): void {
    $game = gameWithNumberedRosters();
    $game->recordToss(TeamSide::Home, TeamAB::TeamA);

    Livewire::test(Court::class, [
        'gameId' => $game->getKey(),
        'gameState' => gameStateWithSubmittedLineups([
            'set_number' => 1,
            'sets_won_team_a' => 1,
            'set_in_progress' => false,
        ]),
    ])
        ->assertSeeInOrder([
            '2',
            '9',
            '3',
            '12',
        ]);
});

test('court keeps lineup submission order aligned with left and right sides in set one', function (): void {
    $game = gameWithNumberedRosters();
    $game->recordToss(TeamSide::Home, TeamAB::TeamA);

    Livewire::test(Court::class, [
        'gameId' => $game->getKey(),
        'gameState' => gameState([
            'set_number' => 1,
            'set_in_progress' => false,
        ]),
    ])->assertSeeInOrder([
        'submit-lineup-team_a',
        'submit-lineup-team_b',
    ]);
});

test('court swaps lineup submission order as soon as a set ends before the next set starts', function (): void {
    $game = gameWithNumberedRosters();
    $game->recordToss(TeamSide::Home, TeamAB::TeamA);

    Livewire::test(Court::class, [
        'gameId' => $game->getKey(),
        'gameState' => gameState([
            'set_number' => 1,
            'sets_won_team_a' => 1,
            'set_in_progress' => false,
        ]),
    ])->assertSeeInOrder([
        'submit-lineup-team_b',
        'submit-lineup-team_a',
    ]);
});

test('court does not render rally winner controls', function (): void {
    $game = Game::factory()->create();

    Livewire::test(Court::class, [
        'gameId' => $game->getKey(),
        'gameState' => gameState([
            'set_number' => 1,
            'set_in_progress' => true,
        ]),
    ])
        ->assertDontSee('Winner')
        ->assertDontSeeHtml('data-rally-winner-button="team_a"')
        ->assertDontSeeHtml('data-rally-winner-button="team_b"');
});

test('rally winner controls show buttons only while a set is in progress and game is not ended', function (): void {
    $game = Game::factory()->create();

    Livewire::test(RallyWinnerControls::class, [
        'gameId' => $game->getKey(),
        'gameState' => gameState(['set_number' => 1, 'set_in_progress' => false]),
    ])
        ->assertDontSee('Winner')
        ->assertDontSeeHtml('data-rally-winner-button="team_a"')
        ->assertDontSeeHtml('data-rally-winner-button="team_b"');

    Livewire::test(RallyWinnerControls::class, [
        'gameId' => $game->getKey(),
        'gameState' => gameState(['set_number' => 1, 'set_in_progress' => true]),
    ])
        ->assertSee('Winner')
        ->assertSeeHtml('data-rally-winner-button="team_a"')
        ->assertSeeHtml('data-rally-winner-button="team_b"');

    Livewire::test(RallyWinnerControls::class, [
        'gameId' => $game->getKey(),
        'gameState' => gameState(['set_number' => 5, 'set_in_progress' => true, 'game_ended' => true]),
    ])
        ->assertDontSee('Winner')
        ->assertDontSeeHtml('data-rally-winner-button="team_a"')
        ->assertDontSeeHtml('data-rally-winner-button="team_b"');
});

test('rally winner controls swap sides as soon as sides swap', function (): void {
    $game = Game::factory()->create();

    Livewire::test(RallyWinnerControls::class, [
        'gameId' => $game->getKey(),
        'gameState' => gameState([
            'set_number' => 2,
            'sets_won_team_a' => 1,
            'set_in_progress' => true,
        ]),
    ])
        ->assertSeeHtml('data-rally-winner-side-team="left-team_b"')
        ->assertSeeHtml('data-rally-winner-side-team="right-team_a"');
});

test('rally winner controls record rally winner for the selected team and dispatches a refresh event', function (): void {
    $game = gameWithStartedSet();

    Livewire::test(RallyWinnerControls::class, [
        'gameId' => $game->getKey(),
        'gameState' => gameState(['set_number' => 1, 'set_in_progress' => true]),
    ])
        ->assertSeeHtml('data-rally-winner-button="team_a"')
        ->assertSeeHtml('data-rally-winner-side-team="left-team_a"')
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
    $homePlayers = Player::factory()->for($game->homeTeam)->count(6)->create();
    foreach ($homePlayers as $index => $player) {
        $game->addPlayer($player, number: $index + 1);
    }

    $awayPlayers = Player::factory()->for($game->awayTeam)->count(6)->create();
    foreach ($awayPlayers as $index => $player) {
        $game->addPlayer($player, number: $index + 11);
    }

    $game->recordToss(TeamSide::Home, TeamAB::TeamA);
    $game->recordLineup(1, TeamAB::TeamA, [1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5, 6 => 6]);
    $game->recordLineup(1, TeamAB::TeamB, [1 => 11, 2 => 12, 3 => 13, 4 => 14, 5 => 15, 6 => 16]);
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

/**
 * @param  array<string, mixed>  $attributes
 */
function gameStateWithSubmittedLineups(array $attributes): GameState
{
    return gameState(array_merge([
        'rotation_team_a' => [1 => 999],
        'rotation_team_b' => [1 => 998],
    ], $attributes));
}
