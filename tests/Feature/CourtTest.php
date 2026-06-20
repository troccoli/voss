<?php

declare(strict_types=1);

use App\Data\GameState\GameState;
use App\Enums\GameEventType;
use App\Enums\MisconductSanction;
use App\Enums\MisconductSubjectType;
use App\Enums\StaffRole;
use App\Enums\TeamAB;
use App\Enums\TeamSide;
use App\Events\Payloads\DelayPenaltyRecordedPayload;
use App\Events\Payloads\DelayWarningRecordedPayload;
use App\Events\Payloads\MisconductRecordedPayload;
use App\Events\Payloads\RallyEndedPayload;
use App\Livewire\Court;
use App\Livewire\RallyWinnerControls;
use App\Models\Game;
use App\Models\Player;
use App\Models\Staff;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('game.between_sets_duration', 0);
});

test('court does not show player lists before toss is submitted', function (): void {
    $game = gameWithNumberedRosters();

    Livewire::test(Court::class, ['gameState' => gameState(['set_number' => 0])])
        ->assertDontSee('Submit Lineup')
        ->assertDontSeeHtml('data-team-roster-number="3"')
        ->assertDontSeeHtml('data-team-roster-number="12"')
        ->assertDontSeeHtml('data-team-roster-number="2"')
        ->assertDontSeeHtml('data-team-roster-number="9"')
        ->assertDontSeeHtml('data-misconduct-controls="left"')
        ->assertDontSeeHtml('data-misconduct-controls="right"')
        ->assertDontSeeHtml('data-delay-controls="left"')
        ->assertDontSeeHtml('data-delay-controls="right"')
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

    Livewire::test(Court::class, ['gameState' => gameStateWithSubmittedLineups(['set_number' => 1])])
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

    Livewire::test(Court::class, ['gameState' => gameStateWithSubmittedLineups(['set_number' => 2, 'sets_won_team_a' => 1])])
        ->assertSeeInOrder([
            '2',
            '9',
            '3',
            '12',
        ]);

    Livewire::test(Court::class, ['gameState' => gameStateWithSubmittedLineups(['set_number' => 3, 'sets_won_team_a' => 1, 'sets_won_team_b' => 1])])
        ->assertSeeInOrder([
            '3',
            '12',
            '2',
            '9',
        ]);

    Livewire::test(Court::class, ['gameState' => gameStateWithSubmittedLineups(['set_number' => 4, 'sets_won_team_a' => 2, 'sets_won_team_b' => 1])])
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

    Livewire::test(Court::class, ['gameState' => gameStateWithSubmittedLineups(['set_number' => 1])])
        ->assertSeeInOrder([
            '2',
            '9',
            '3',
            '12',
        ]);

    Livewire::test(Court::class, ['gameState' => gameStateWithSubmittedLineups(['set_number' => 5, 'sets_won_team_a' => 2, 'sets_won_team_b' => 2])])
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

test('court uses the fifth set toss to place the correct team on the left before lineup submission', function (): void {
    $game = gameWithNumberedRosters();
    $game->recordToss(TeamSide::Home, TeamAB::TeamA);

    Livewire::test(Court::class, [
        'gameState' => gameState([
            'set_number' => 4,
            'sets_won_team_a' => 2,
            'sets_won_team_b' => 2,
            'fifth_set_left_team' => TeamAB::TeamB->value,
            'serving_team' => TeamAB::TeamB->value,
            'set_in_progress' => false,
        ]),
    ])
        ->assertSeeInOrder([
            'submit-lineup-team_b',
            'submit-lineup-team_a',
        ])
        ->assertSeeInOrder([
            '2',
            '9',
            '3',
            '12',
        ]);
});

test('court swaps sides in the fifth set after the 8-point side change', function (): void {
    $game = gameWithNumberedRosters();
    $game->recordToss(TeamSide::Home, TeamAB::TeamA);

    Livewire::test(Court::class, [
        'gameState' => gameState([
            'set_number' => 5,
            'sets_won_team_a' => 2,
            'sets_won_team_b' => 2,
            'fifth_set_left_team' => TeamAB::TeamB->value,
            'fifth_set_side_swapped' => true,
            'serving_team' => TeamAB::TeamA->value,
            'rotation_team_a' => [1 => 12],
            'rotation_team_b' => [1 => 9],
            'set_in_progress' => true,
        ]),
    ])
        ->assertSeeHtml('data-court-marker="left-team_b-1"')
        ->assertSeeHtml('data-court-marker="right-team_a-1"')
        ->assertSeeHtml('data-court-serving-player="1"')
        ->assertSeeHtml('-right-10 top-[14%]');
});

test('court renders rally winner controls when set is in progress', function (): void {
    $game = Game::factory()->create();

    Livewire::test(Court::class, [
        'gameState' => gameState(['set_number' => 1, 'set_in_progress' => false]),
    ])
        ->assertDontSee('Winner')
        ->assertDontSeeHtml('data-rally-winner-button="team_a"')
        ->assertDontSeeHtml('data-rally-winner-button="team_b"');

    Livewire::test(Court::class, [
        'gameState' => gameState(['set_number' => 1, 'set_in_progress' => true]),
    ])
        ->assertSee('Winner')
        ->assertSeeHtml('data-rally-winner-button="team_a"')
        ->assertSeeHtml('data-rally-winner-button="team_b"');
});

test('court renders misconduct controls on both sides', function (): void {
    $game = gameWithNumberedRosters();
    $game->recordToss(TeamSide::Home, TeamAB::TeamA);

    Livewire::test(Court::class, [
        'gameState' => gameStateWithSubmittedLineups(['set_number' => 1]),
    ])
        ->assertSeeHtml('data-misconduct-controls="left"')
        ->assertSeeHtml('data-misconduct-controls="right"')
        ->assertSeeHtml('data-misconduct-button="warning"')
        ->assertSeeHtml('data-misconduct-button="penalty"')
        ->assertSeeHtml('data-misconduct-button="expulsion"')
        ->assertSeeHtml('data-misconduct-button="disqualification"')
        ->assertSee('Misconduct')
        ->assertSeeHtml('aria-label="Minor misconduct"')
        ->assertSeeHtml('aria-label="Penalty"')
        ->assertSeeHtml('aria-label="Expulsion"')
        ->assertSeeHtml('aria-label="Disqualification"')
        ->assertSeeHtml('data-delay-controls="left"')
        ->assertSeeHtml('data-delay-controls="right"')
        ->assertSeeHtml('data-delay-button="delay-warning"')
        ->assertSeeHtml('data-delay-button="delay-penalty"')
        ->assertSee('Delay')
        ->assertSeeHtml('aria-label="Delay warning"')
        ->assertSeeHtml('aria-label="Delay penalty"')
        ->assertSeeHtml('yellow-card.svg')
        ->assertSeeHtml('red-card.svg')
        ->assertSeeHtml('yellow-red-card.svg')
        ->assertSeeHtml('yellow-red-side-by-side-card.svg')
        ->assertDontSeeHtml('<span class="text-left text-xs leading-tight">Minor misconduct</span>')
        ->assertDontSeeHtml('<span class="text-left text-xs leading-tight">Penalty</span>')
        ->assertDontSeeHtml('<span class="text-left text-xs leading-tight">Expulsion</span>')
        ->assertDontSeeHtml('<span class="text-left text-xs leading-tight">Disqualification</span>')
        ->assertDontSeeHtml('<span class="text-left text-xs leading-tight">Delay warning</span>')
        ->assertDontSeeHtml('<span class="text-left text-xs leading-tight">Delay penalty</span>');
});

test('rally winner controls show button only while a set is in progress and game is not ended', function (): void {
    $game = Game::factory()->create();

    Livewire::test(RallyWinnerControls::class, [
        'gameState' => gameState(['set_number' => 1, 'set_in_progress' => false]),
        'side' => 'left',
    ])
        ->assertDontSee('Winner')
        ->assertDontSeeHtml('data-rally-winner-button="team_a"');

    Livewire::test(RallyWinnerControls::class, [
        'gameState' => gameState(['set_number' => 1, 'set_in_progress' => true]),
        'side' => 'left',
    ])
        ->assertSee('Winner')
        ->assertSeeHtml('data-rally-winner-button="team_a"');

    Livewire::test(RallyWinnerControls::class, [
        'gameState' => gameState(['set_number' => 1, 'set_in_progress' => true]),
        'side' => 'right',
    ])
        ->assertSee('Winner')
        ->assertSeeHtml('data-rally-winner-button="team_b"');

    Livewire::test(RallyWinnerControls::class, [
        'gameState' => gameState(['set_number' => 5, 'set_in_progress' => true, 'game_ended' => true]),
        'side' => 'left',
    ])
        ->assertDontSee('Winner')
        ->assertDontSeeHtml('data-rally-winner-button="team_a"');
});

test('rally winner controls swap sides as soon as sides swap', function (): void {
    $game = Game::factory()->create();

    $state = gameState([
        'set_number' => 2,
        'sets_won_team_a' => 1,
        'set_in_progress' => true,
    ]);

    Livewire::test(RallyWinnerControls::class, [
        'gameState' => $state,
        'side' => 'left',
    ])
        ->assertSeeHtml('data-rally-winner-side-team="left-team_b"');

    Livewire::test(RallyWinnerControls::class, [
        'gameState' => $state,
        'side' => 'right',
    ])
        ->assertSeeHtml('data-rally-winner-side-team="right-team_a"');
});

test('rally winner controls record rally winner for the selected team and dispatches a refresh event', function (): void {
    $game = gameWithStartedSet();

    Livewire::test(RallyWinnerControls::class, [
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

test('court delay warning button confirms and records a delay warning', function (): void {
    $game = gameWithStartedSet();

    Livewire::test(Court::class, [
        'gameState' => $game->stateAt(),
    ])
        ->call('requestDelayWarning', TeamAB::TeamA->value)
        ->assertSet('pendingDelayWarningTeam', TeamAB::TeamA->value)
        ->call('recordPendingDelayWarning')
        ->assertSet('pendingDelayWarningTeam', null)
        ->assertSet('delaySanctionRecordedTitle', 'Delay warning recorded')
        ->assertSee('A delay warning has been recorded for Team A.')
        ->assertDontSeeHtml('data-delay-warning-recorded-indicator="left-team_a"')
        ->assertDispatched('game-event-recorded');

    $latestEvent = $game->fresh()->events->last();

    expect($latestEvent)->not->toBeNull()
        ->and($latestEvent->type)->toBe(GameEventType::DelayWarningRecorded)
        ->and($latestEvent->payload)->toBeInstanceOf(DelayWarningRecordedPayload::class)
        ->and($latestEvent->payload->team)->toBe(TeamAB::TeamA)
        ->and($latestEvent->payload->requestType)->toBeNull()
        ->and($game->fresh()->stateAt()->delayWarningsTeamA)->toBe(1);
});

test('court delay warning button can record a warning between sets', function (): void {
    $game = gameWithStartedSet();

    for ($index = 0; $index < 25; $index++) {
        $game->recordRallyWinner(TeamAB::TeamA);
    }

    $betweenSetsState = $game->fresh()->stateAt();

    expect($betweenSetsState->setInProgress)->toBeFalse();

    Livewire::test(Court::class, [
        'gameState' => $betweenSetsState,
    ])
        ->call('requestDelayWarning', TeamAB::TeamB->value)
        ->call('recordPendingDelayWarning')
        ->assertSet('delaySanctionRecordedTitle', 'Delay warning recorded')
        ->assertSee('A delay warning has been recorded for Team B.')
        ->assertDispatched('game-event-recorded');

    $latestEvent = $game->fresh()->events->last();

    expect($latestEvent)->not->toBeNull()
        ->and($latestEvent->type)->toBe(GameEventType::DelayWarningRecorded)
        ->and($latestEvent->payload)->toBeInstanceOf(DelayWarningRecordedPayload::class)
        ->and($latestEvent->payload->team)->toBe(TeamAB::TeamB)
        ->and($game->fresh()->stateAt()->delayWarningsTeamB)->toBe(1);
});

test('court disables the delay warning button when a warning already exists', function (): void {
    $game = gameWithStartedSet();
    $game->recordDelayWarning(TeamAB::TeamA);

    $component = Livewire::test(Court::class, [
        'gameState' => $game->stateAt(),
    ]);

    expect($component->html())
        ->toMatch('/<button[^>]*(?:disabled[^>]*data-delay-button="delay-warning"|data-delay-button="delay-warning"[^>]*disabled)[^>]*data-delay-side-team="left-team_a"/')
        ->toContain('data-delay-warning-recorded-indicator="left-team_a"')
        ->toContain('opacity-60');
});

test('court delay penalty button confirms and records a repeatable delay penalty', function (): void {
    $game = gameWithStartedSet();
    $game->recordDelayWarning(TeamAB::TeamA);

    Livewire::test(Court::class, [
        'gameState' => $game->stateAt(),
    ])
        ->call('requestDelayPenalty', TeamAB::TeamA->value)
        ->assertSet('pendingDelayPenaltyTeam', TeamAB::TeamA->value)
        ->call('recordPendingDelayPenalty')
        ->assertSet('pendingDelayPenaltyTeam', null)
        ->assertSet('delaySanctionRecordedTitle', 'Delay penalty recorded')
        ->assertSee('A delay penalty has been recorded for Team A.')
        ->assertDispatched('game-event-recorded');

    $state = $game->fresh()->stateAt();
    $latestEvent = $game->fresh()->events->last();

    expect($latestEvent)->not->toBeNull()
        ->and($latestEvent->type)->toBe(GameEventType::DelayPenaltyRecorded)
        ->and($latestEvent->payload)->toBeInstanceOf(DelayPenaltyRecordedPayload::class)
        ->and($latestEvent->payload->team)->toBe(TeamAB::TeamA)
        ->and($latestEvent->payload->awardedTeam)->toBe(TeamAB::TeamB)
        ->and($latestEvent->payload->requestType)->toBeNull()
        ->and($state->scoreTeamB)->toBe(1)
        ->and($state->servingTeam)->toBe(TeamAB::TeamB)
        ->and($state->delayPenaltiesTeamA)->toBe(1);

    Livewire::test(Court::class, [
        'gameState' => $game->stateAt(),
    ])
        ->call('requestDelayPenalty', TeamAB::TeamA->value)
        ->call('recordPendingDelayPenalty')
        ->assertSet('delaySanctionRecordedTitle', 'Delay penalty recorded')
        ->assertDispatched('game-event-recorded');

    expect($game->fresh()->stateAt()->delayPenaltiesTeamA)->toBe(2);

    $component = Livewire::test(Court::class, [
        'gameState' => $game->stateAt(),
    ]);

    expect($component->html())
        ->toMatch('/<button(?![^>]*disabled=)[^>]*data-delay-button="delay-penalty"[^>]*data-delay-side-team="left-team_a"/')
        ->not->toContain('data-delay-penalty-locked-indicator="left-team_a"');
});

test('court disables the delay penalty button with a lock until a warning exists', function (): void {
    $game = gameWithStartedSet();

    $component = Livewire::test(Court::class, [
        'gameState' => $game->stateAt(),
    ])
        ->assertViewHas('leftDelayPenaltyDisabled', true);

    expect($component->html())
        ->toMatch('/<button[^>]*(?:disabled[^>]*data-delay-button="delay-penalty"|data-delay-button="delay-penalty"[^>]*disabled)[^>]*data-delay-side-team="left-team_a"/')
        ->toContain('data-delay-penalty-locked-indicator="left-team_a"')
        ->toContain('data-delay-penalty-locked-icon');
});

test('court misconduct flow shows rostered players and staff for the team', function (): void {
    $game = gameWithStartedSet();
    $staff = Staff::factory()->for($game->homeTeam)->create();
    $game->addStaff($staff, StaffRole::Coach);

    $player = $game->homePlayers()->where('number', 1)->firstOrFail();

    Livewire::test(Court::class, [
        'gameState' => $game->stateAt(),
    ])
        ->call('requestMisconduct', TeamAB::TeamA->value, MisconductSanction::Penalty->value)
        ->assertSet('pendingMisconductTeam', TeamAB::TeamA->value)
        ->assertSet('pendingMisconductSanction', MisconductSanction::Penalty->value)
        ->assertViewHas('misconductSubjects', function (array $subjects) use ($player, $staff): bool {
            $hasPlayer = collect($subjects['players'])->contains(fn (array $subject): bool => $subject['subject_id'] === $player->getKey()
                && $subject['subject_type'] === MisconductSubjectType::Player->value
                && $subject['marker'] === '1');

            $hasStaff = collect($subjects['staff'])->contains(fn (array $subject): bool => $subject['subject_id'] === $staff->getKey()
                && $subject['subject_type'] === MisconductSubjectType::Staff->value
                && $subject['marker'] === 'C');

            return $hasPlayer && $hasStaff;
        })
        ->assertSeeHtml('data-misconduct-subject-button="player-'.$player->getKey().'"')
        ->assertSeeHtml('data-misconduct-subject-button="staff-'.$staff->getKey().'"')
        ->assertSeeHtml('rounded-full')
        ->assertSeeHtml('bg-blue-600')
        ->assertDontSee($player->first_name)
        ->assertDontSee($player->last_name)
        ->assertDontSee($staff->first_name)
        ->assertDontSee($staff->last_name);
});

test('court misconduct flow records a sanction against a player after confirmation', function (): void {
    $game = gameWithStartedSet();
    $player = $game->homePlayers()->where('number', 1)->firstOrFail();

    Livewire::test(Court::class, [
        'gameState' => $game->stateAt(),
    ])
        ->call('requestMisconduct', TeamAB::TeamA->value, MisconductSanction::Penalty->value)
        ->call('selectMisconductSubject', MisconductSubjectType::Player->value, $player->getKey())
        ->assertSet('pendingMisconductSubjectType', MisconductSubjectType::Player->value)
        ->assertSet('pendingMisconductSubjectId', $player->getKey())
        ->call('recordPendingMisconduct')
        ->assertSet('pendingMisconductTeam', null)
        ->assertSet('delaySanctionRecordedTitle', 'Misconduct recorded')
        ->assertSee('Penalty has been recorded for 1')
        ->assertDispatched('game-event-recorded');

    $latestEvent = $game->fresh()->events->last();

    expect($latestEvent)->not->toBeNull()
        ->and($latestEvent->type)->toBe(GameEventType::MisconductRecorded)
        ->and($latestEvent->payload)->toBeInstanceOf(MisconductRecordedPayload::class)
        ->and($latestEvent->payload->team)->toBe(TeamAB::TeamA)
        ->and($latestEvent->payload->subjectType)->toBe(MisconductSubjectType::Player)
        ->and($latestEvent->payload->subjectId)->toBe($player->getKey())
        ->and($latestEvent->payload->sanction)->toBe(MisconductSanction::Penalty);
});

test('court misconduct flow records a sanction against staff after confirmation', function (): void {
    $game = gameWithStartedSet();
    $staff = Staff::factory()->for($game->homeTeam)->create();
    $game->addStaff($staff, StaffRole::Coach);

    Livewire::test(Court::class, [
        'gameState' => $game->stateAt(),
    ])
        ->call('requestMisconduct', TeamAB::TeamA->value, MisconductSanction::Expulsion->value)
        ->call('selectMisconductSubject', MisconductSubjectType::Staff->value, $staff->getKey())
        ->assertSet('pendingMisconductSubjectType', MisconductSubjectType::Staff->value)
        ->assertSet('pendingMisconductSubjectId', $staff->getKey())
        ->call('recordPendingMisconduct')
        ->assertSet('pendingMisconductTeam', null)
        ->assertSet('delaySanctionRecordedTitle', 'Misconduct recorded')
        ->assertSee('Expulsion has been recorded for C')
        ->assertDispatched('game-event-recorded');

    $latestEvent = $game->fresh()->events->last();

    expect($latestEvent)->not->toBeNull()
        ->and($latestEvent->type)->toBe(GameEventType::MisconductRecorded)
        ->and($latestEvent->payload)->toBeInstanceOf(MisconductRecordedPayload::class)
        ->and($latestEvent->payload->team)->toBe(TeamAB::TeamA)
        ->and($latestEvent->payload->subjectType)->toBe(MisconductSubjectType::Staff)
        ->and($latestEvent->payload->subjectId)->toBe($staff->getKey())
        ->and($latestEvent->payload->sanction)->toBe(MisconductSanction::Expulsion);
});

test('court disables minor misconduct with a check once it has been recorded for the team', function (): void {
    $game = gameWithStartedSet();
    $player = $game->homePlayers()->where('number', 1)->firstOrFail();

    $game->recordMisconduct(
        team: TeamAB::TeamA,
        subjectType: MisconductSubjectType::Player,
        subjectId: $player->getKey(),
        sanction: MisconductSanction::Warning,
    );

    $component = Livewire::test(Court::class, [
        'gameState' => $game->fresh()->stateAt(),
    ])
        ->assertViewHas('leftMinorMisconductDisabled', true);

    expect($component->html())
        ->toMatch('/<button[^>]*(?:disabled[^>]*data-misconduct-button="warning"|data-misconduct-button="warning"[^>]*disabled)[^>]*data-misconduct-side-team="left-team_a"/')
        ->toContain('data-minor-misconduct-recorded-indicator="left-team_a"')
        ->toContain('opacity-60');
});

test('court misconduct picker marks people unavailable for same or lower sanctions', function (): void {
    $game = gameWithStartedSet();
    $player = $game->homePlayers()->where('number', 1)->firstOrFail();
    $staff = Staff::factory()->for($game->homeTeam)->create();
    $game->addStaff($staff, StaffRole::Coach);

    $game->recordMisconduct(
        team: TeamAB::TeamA,
        subjectType: MisconductSubjectType::Player,
        subjectId: $player->getKey(),
        sanction: MisconductSanction::Penalty,
    );
    $game->recordMisconduct(
        team: TeamAB::TeamA,
        subjectType: MisconductSubjectType::Staff,
        subjectId: $staff->getKey(),
        sanction: MisconductSanction::Expulsion,
    );

    $component = Livewire::test(Court::class, [
        'gameState' => $game->fresh()->stateAt(),
    ])
        ->call('requestMisconduct', TeamAB::TeamA->value, MisconductSanction::Penalty->value)
        ->assertViewHas('misconductSubjects', function (array $subjects) use ($player, $staff): bool {
            $playerUnavailable = collect($subjects['players'])->contains(fn (array $subject): bool => $subject['subject_id'] === $player->getKey()
                && $subject['unavailable'] === true
                && str_ends_with((string) $subject['unavailable_icon'], '/icons/red-card.svg'));

            $staffUnavailable = collect($subjects['staff'])->contains(fn (array $subject): bool => $subject['subject_id'] === $staff->getKey()
                && $subject['unavailable'] === true
                && str_ends_with((string) $subject['unavailable_icon'], '/icons/yellow-red-card.svg'));

            return $playerUnavailable && $staffUnavailable;
        });

    expect($component->html())
        ->toContain('data-misconduct-subject-unavailable-indicator="player-'.$player->getKey().'"')
        ->toContain('data-misconduct-subject-unavailable-indicator="staff-'.$staff->getKey().'"')
        ->toContain('red-card.svg')
        ->toContain('yellow-red-card.svg')
        ->not->toContain('x-mark');

    Livewire::test(Court::class, [
        'gameState' => $game->fresh()->stateAt(),
    ])
        ->call('requestMisconduct', TeamAB::TeamA->value, MisconductSanction::Expulsion->value)
        ->assertViewHas('misconductSubjects', function (array $subjects) use ($player, $staff): bool {
            $playerAvailable = collect($subjects['players'])->contains(fn (array $subject): bool => $subject['subject_id'] === $player->getKey()
                && $subject['unavailable'] === false);

            $staffUnavailable = collect($subjects['staff'])->contains(fn (array $subject): bool => $subject['subject_id'] === $staff->getKey()
                && $subject['unavailable'] === true);

            return $playerAvailable && $staffUnavailable;
        });
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
