<?php

declare(strict_types=1);

use App\Data\GameState\GameState;
use App\Enums\GameEventType;
use App\Enums\StaffRole;
use App\Enums\TeamAB;
use App\Enums\TeamSide;
use App\Livewire\TeamRoster;
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
        'gameState' => submittedLineupState(),
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
        'gameState' => submittedLineupState(),
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
        'gameState' => submittedLineupState(),
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
        'gameState' => submittedLineupState(),
    ])
        ->assertSeeInOrder([
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
        'gameState' => submittedLineupState(),
    ])
        ->assertSeeHtml('data-team-roster-number="3"')
        ->call('$refresh')
        ->assertSeeHtml('data-team-roster-number="3"');
});

test('team roster shows placeholders when lineup is not submitted', function (): void {
    $homeTeam = Team::factory()->create();
    $awayTeam = Team::factory()->create();
    $game = Game::factory()->betweenTeams($homeTeam, $awayTeam)->create();
    $homePlayers = Player::factory()->for($homeTeam)->count(9)->create();

    foreach ($homePlayers as $index => $player) {
        $game->addPlayer($player, number: $index + 1, isLibero: $index === 8);
    }

    Livewire::test(TeamRoster::class, [
        'gameId' => $game->getKey(),
        'team' => TeamAB::TeamA,
        'leftSide' => true,
    ])
        ->assertSeeHtml('data-team-roster-placeholder="1"')
        ->assertSeeHtml('data-team-roster-placeholder="2"')
        ->assertDontSeeHtml('data-team-roster-placeholder="3"')
        ->assertDontSeeHtml('data-team-roster-number="1"')
        ->assertDontSeeHtml('data-team-roster-number="8"');
});

test('team roster renders left-side staff role circles in reverse row order for team a', function (): void {
    $game = gameWithNumberedRostersForTeamRoster();

    Livewire::test(TeamRoster::class, [
        'gameId' => $game->getKey(),
        'team' => TeamAB::TeamA,
        'leftSide' => true,
    ])
        ->assertSeeHtml('data-team-roster-staff-list')
        ->assertSeeHtml('flex-row-reverse')
        ->assertSeeInOrder([
            'data-team-roster-staff-role="C"',
            'data-team-roster-staff-role="A1"',
            'data-team-roster-staff-role="A2"',
            'data-team-roster-staff-role="D"',
            'data-team-roster-staff-role="T"',
        ]);
});

test('team roster renders right-side staff role circles left to right for team b', function (): void {
    $game = gameWithNumberedRostersForTeamRoster();

    Livewire::test(TeamRoster::class, [
        'gameId' => $game->getKey(),
        'team' => TeamAB::TeamB,
        'leftSide' => false,
    ])
        ->assertSeeHtml('data-team-roster-staff-list')
        ->assertDontSeeHtml('flex-row-reverse')
        ->assertSeeInOrder([
            'data-team-roster-staff-role="C"',
            'data-team-roster-staff-role="A1"',
            'data-team-roster-staff-role="A2"',
            'data-team-roster-staff-role="D"',
            'data-team-roster-staff-role="T"',
        ]);
});

test('team roster applies left-side staff orientation even when the displayed team is team b', function (): void {
    $game = gameWithNumberedRostersForTeamRoster();

    Livewire::test(TeamRoster::class, [
        'gameId' => $game->getKey(),
        'team' => TeamAB::TeamB,
        'leftSide' => true,
    ])
        ->assertSeeHtml('data-team-roster-staff-list')
        ->assertSeeHtml('flex-row-reverse')
        ->assertSeeInOrder([
            'data-team-roster-staff-role="C"',
            'data-team-roster-staff-role="A1"',
            'data-team-roster-staff-role="A2"',
            'data-team-roster-staff-role="D"',
            'data-team-roster-staff-role="T"',
        ]);
});

test('team roster applies right-side staff orientation even when the displayed team is team a', function (): void {
    $game = gameWithNumberedRostersForTeamRoster();

    Livewire::test(TeamRoster::class, [
        'gameId' => $game->getKey(),
        'team' => TeamAB::TeamA,
        'leftSide' => false,
    ])
        ->assertSeeHtml('data-team-roster-staff-list')
        ->assertDontSeeHtml('flex-row-reverse')
        ->assertSeeInOrder([
            'data-team-roster-staff-role="C"',
            'data-team-roster-staff-role="A1"',
            'data-team-roster-staff-role="A2"',
            'data-team-roster-staff-role="D"',
            'data-team-roster-staff-role="T"',
        ]);
});

test('team roster omits staff role circles when those roles are not on the roster', function (): void {
    $homeTeam = Team::factory()->create();
    $awayTeam = Team::factory()->create();
    $game = Game::factory()->betweenTeams($homeTeam, $awayTeam)->create();

    $homeCoach = Staff::factory()->for($homeTeam)->create();
    $homeDoctor = Staff::factory()->for($homeTeam)->create();

    $game->addStaff($homeCoach, StaffRole::Coach);
    $game->addStaff($homeDoctor, StaffRole::Doctor);

    Livewire::test(TeamRoster::class, [
        'gameId' => $game->getKey(),
        'team' => TeamAB::TeamA,
        'leftSide' => true,
    ])
        ->assertSeeHtml('data-team-roster-staff-role="C"')
        ->assertSeeHtml('data-team-roster-staff-role="D"')
        ->assertDontSeeHtml('data-team-roster-staff-role="A1"')
        ->assertDontSeeHtml('data-team-roster-staff-role="A2"')
        ->assertDontSeeHtml('data-team-roster-staff-role="T"');
});

test('timeout card shows modal trigger when a set is in progress', function (): void {
    $game = gameWithActiveSetForTeamRoster();

    Livewire::test(TeamRoster::class, [
        'gameId' => $game->getKey(),
        'team' => TeamAB::TeamA,
        'leftSide' => true,
        'gameState' => $game->stateAt(),
    ])->assertSeeHtml('data-team-roster-timeouts')
        ->assertSeeHtml('request-timeout-team_a');
});

test('timeout card is not a modal trigger when no set is in progress', function (): void {
    $homeTeam = Team::factory()->create();
    $awayTeam = Team::factory()->create();
    $game = Game::factory()->betweenTeams($homeTeam, $awayTeam)->create();

    Livewire::test(TeamRoster::class, [
        'gameId' => $game->getKey(),
        'team' => TeamAB::TeamA,
        'leftSide' => true,
    ])->assertSeeHtml('data-team-roster-timeouts')
        ->assertDontSeeHtml('request-timeout-team_a');
});

test('timeout card remains interactive when team has used all timeouts', function (): void {
    $game = gameWithActiveSetForTeamRoster();
    $game->recordTimeOut(TeamAB::TeamA);
    $game->recordTimeOut(TeamAB::TeamA);

    Livewire::test(TeamRoster::class, [
        'gameId' => $game->getKey(),
        'team' => TeamAB::TeamA,
        'leftSide' => true,
        'gameState' => $game->stateAt(),
    ])->assertSeeHtml('data-team-roster-timeouts')
        ->assertSeeHtml('request-timeout-team_a');
});

test('requesting a timeout dispatches hasTimeoutLeft true when timeouts remain', function (): void {
    $game = gameWithActiveSetForTeamRoster();

    Livewire::test(TeamRoster::class, [
        'gameId' => $game->getKey(),
        'team' => TeamAB::TeamA,
        'leftSide' => true,
        'gameState' => $game->stateAt(),
    ])
        ->call('requestTimeout')
        ->assertDispatched('timeout-recorded', team: 'team_a', hasTimeoutLeft: true);
});

test('requesting a timeout dispatches hasTimeoutLeft false and does not record an event when no timeouts remain', function (): void {
    $game = gameWithActiveSetForTeamRoster();
    $game->recordTimeOut(TeamAB::TeamA);
    $game->recordTimeOut(TeamAB::TeamA);
    $eventCountBefore = $game->events()->count();

    Livewire::test(TeamRoster::class, [
        'gameId' => $game->getKey(),
        'team' => TeamAB::TeamA,
        'leftSide' => true,
        'gameState' => $game->stateAt(),
    ])
        ->call('requestTimeout')
        ->assertDispatched('timeout-recorded', team: 'team_a', hasTimeoutLeft: false)
        ->assertNotDispatched('game-event-recorded');

    expect($game->events()->count())->toBe($eventCountBefore);
});

test('timeout card becomes interactive again for a new set after two timeouts in the previous set', function (): void {
    $game = gameWithActiveSetForTeamRoster();
    $game->recordTimeOut(TeamAB::TeamA);
    $game->recordTimeOut(TeamAB::TeamA);

    // End set 1 (auto-ended by reactor at 25-0) and start set 2
    for ($i = 0; $i < 25; $i++) {
        $game->recordRallyWinner(TeamAB::TeamA);
    }
    $game->recordLineup(2, TeamAB::TeamA, [1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5, 6 => 6]);
    $game->recordLineup(2, TeamAB::TeamB, [1 => 11, 2 => 12, 3 => 13, 4 => 14, 5 => 15, 6 => 16]);
    $game->recordSetStarted();

    Livewire::test(TeamRoster::class, [
        'gameId' => $game->getKey(),
        'team' => TeamAB::TeamA,
        'leftSide' => true,
        'gameState' => $game->stateAt(),
    ])->assertSeeHtml('request-timeout-team_a');
});

test('requesting a timeout records the event and dispatches events', function (): void {
    $game = gameWithActiveSetForTeamRoster();

    Livewire::test(TeamRoster::class, [
        'gameId' => $game->getKey(),
        'team' => TeamAB::TeamA,
        'leftSide' => true,
        'gameState' => $game->stateAt(),
    ])
        ->call('requestTimeout')
        ->assertHasNoErrors()
        ->assertDispatched('game-event-recorded')
        ->assertDispatched('timeout-recorded', team: 'team_a');

    $latestEvent = $game->fresh()->events->last();
    expect($latestEvent->type)->toBe(GameEventType::TimeOutRequested);
});

test('requesting a timeout adds an error when no set is in progress', function (): void {
    $homeTeam = Team::factory()->create();
    $awayTeam = Team::factory()->create();
    $game = Game::factory()->betweenTeams($homeTeam, $awayTeam)->create();

    Livewire::test(TeamRoster::class, [
        'gameId' => $game->getKey(),
        'team' => TeamAB::TeamA,
        'leftSide' => true,
    ])
        ->call('requestTimeout')
        ->assertHasErrors('timeout')
        ->assertNotDispatched('game-event-recorded')
        ->assertNotDispatched('timeout-recorded');
});

test('substitution card shows modal trigger when a set is in progress and substitutions remain', function (): void {
    $game = gameWithActiveSetForTeamRoster();

    Livewire::test(TeamRoster::class, [
        'gameId' => $game->getKey(),
        'team' => TeamAB::TeamA,
        'leftSide' => true,
        'gameState' => $game->stateAt(),
    ])->assertSeeHtml('data-team-roster-substitutions')
        ->assertSeeHtml('substitution-team_a');
});

test('substitution card is not a modal trigger when no set is in progress', function (): void {
    $homeTeam = Team::factory()->create();
    $awayTeam = Team::factory()->create();
    $game = Game::factory()->betweenTeams($homeTeam, $awayTeam)->create();

    Livewire::test(TeamRoster::class, [
        'gameId' => $game->getKey(),
        'team' => TeamAB::TeamA,
        'leftSide' => true,
    ])->assertSeeHtml('data-team-roster-substitutions')
        ->assertDontSeeHtml('substitution-team_a');
});

test('substitution card shows full modal trigger when all 6 substitutions are used', function (): void {
    $game = gameWithActiveSetForTeamRoster();
    $state = $game->stateAt();

    Livewire::test(TeamRoster::class, [
        'gameId' => $game->getKey(),
        'team' => TeamAB::TeamA,
        'leftSide' => true,
        'gameState' => GameState::fromAttributes(array_merge($state->toAttributes(), ['substitutions_team_a' => 6])),
    ])->assertSeeHtml('data-team-roster-substitutions')
        ->assertSeeHtml('substitution-full-confirm-team_a')
        ->assertDontSeeHtml('name="substitution-team_a"');
});

test('substitution modal shows on-court and bench player numbers', function (): void {
    $game = gameWithActiveSetForTeamRoster();

    Livewire::test(TeamRoster::class, [
        'gameId' => $game->getKey(),
        'team' => TeamAB::TeamA,
        'leftSide' => true,
        'gameState' => $game->stateAt(),
    ])->assertSeeHtml('data-substitution-on-court-number="1"')
        ->assertSeeHtml('data-substitution-on-court-number="6"')
        ->assertDontSeeHtml('data-substitution-bench-number="1"');
});

test('substitution records event and dispatches events', function (): void {
    $game = gameWithActiveSetForTeamRoster();

    Livewire::test(TeamRoster::class, [
        'gameId' => $game->getKey(),
        'team' => TeamAB::TeamA,
        'leftSide' => true,
        'gameState' => $game->stateAt(),
    ])
        ->set('playerOut', '1')
        ->set('playerIn', '7')
        ->call('submitSubstitution')
        ->assertHasNoErrors()
        ->assertDispatched('game-event-recorded')
        ->assertDispatched('substitution-recorded', team: 'team_a');

    $latestEvent = $game->fresh()->events->last();
    expect($latestEvent->type)->toBe(GameEventType::SubstitutionCompleted);
});

test('substitution requires both player numbers', function (): void {
    $game = gameWithActiveSetForTeamRoster();

    Livewire::test(TeamRoster::class, [
        'gameId' => $game->getKey(),
        'team' => TeamAB::TeamA,
        'leftSide' => true,
        'gameState' => $game->stateAt(),
    ])
        ->set('playerOut', '')
        ->set('playerIn', '')
        ->call('submitSubstitution')
        ->assertHasErrors('substitution')
        ->assertNotDispatched('game-event-recorded');
});

test('substitution is rejected when team has used all 6 substitutions', function (): void {
    $game = gameWithActiveSetForTeamRoster();

    for ($i = 0; $i < 6; $i++) {
        $game->recordSubstitution(TeamAB::TeamA, $i + 1, $i + 7);
    }

    Livewire::test(TeamRoster::class, [
        'gameId' => $game->getKey(),
        'team' => TeamAB::TeamA,
        'leftSide' => true,
        'gameState' => $game->stateAt(),
    ])
        ->set('playerOut', '1')
        ->set('playerIn', '7')
        ->call('submitSubstitution')
        ->assertHasErrors('substitution')
        ->assertNotDispatched('game-event-recorded');
});

test('substituted player cannot be substituted again in the same set', function (): void {
    $game = gameWithActiveSetForTeamRoster();
    $game->recordSubstitution(TeamAB::TeamA, 1, 7);

    Livewire::test(TeamRoster::class, [
        'gameId' => $game->getKey(),
        'team' => TeamAB::TeamA,
        'leftSide' => true,
        'gameState' => $game->stateAt(),
    ])
        ->set('playerOut', '7')
        ->set('playerIn', '1')
        ->call('submitSubstitution')
        ->assertHasNoErrors()
        ->assertDispatched('game-event-recorded');

    Livewire::test(TeamRoster::class, [
        'gameId' => $game->fresh()->getKey(),
        'team' => TeamAB::TeamA,
        'leftSide' => true,
        'gameState' => $game->fresh()->stateAt(),
    ])
        ->set('playerOut', '1')
        ->set('playerIn', '7')
        ->call('submitSubstitution')
        ->assertHasErrors('substitution')
        ->assertNotDispatched('game-event-recorded');
});

test('substitution pair can only swap back once', function (): void {
    $game = gameWithActiveSetForTeamRoster();
    $game->recordSubstitution(TeamAB::TeamA, 1, 7);
    $game->recordSubstitution(TeamAB::TeamA, 7, 1);

    Livewire::test(TeamRoster::class, [
        'gameId' => $game->getKey(),
        'team' => TeamAB::TeamA,
        'leftSide' => true,
        'gameState' => $game->stateAt(),
    ])
        ->set('playerOut', '1')
        ->set('playerIn', '7')
        ->call('submitSubstitution')
        ->assertHasErrors('substitution')
        ->assertNotDispatched('game-event-recorded');
});

test('player with active pair constraint can only be replaced by their partner', function (): void {
    $game = gameWithActiveSetForTeamRoster();
    $game->recordSubstitution(TeamAB::TeamA, 1, 7);

    Livewire::test(TeamRoster::class, [
        'gameId' => $game->getKey(),
        'team' => TeamAB::TeamA,
        'leftSide' => true,
        'gameState' => $game->stateAt(),
    ])
        ->set('playerOut', '7')
        ->set('playerIn', '8')
        ->call('submitSubstitution')
        ->assertHasErrors('substitution')
        ->assertNotDispatched('game-event-recorded');
});

test('substitution constraints reset between sets', function (): void {
    $game = gameWithActiveSetForTeamRoster();
    $game->recordSubstitution(TeamAB::TeamA, 1, 7);
    $game->recordSubstitution(TeamAB::TeamA, 7, 1);

    for ($i = 0; $i < 25; $i++) {
        $game->recordRallyWinner(TeamAB::TeamA);
    }

    $game->recordLineup(2, TeamAB::TeamA, [1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5, 6 => 6]);
    $game->recordLineup(2, TeamAB::TeamB, [1 => 11, 2 => 12, 3 => 13, 4 => 14, 5 => 15, 6 => 16]);
    $game->recordSetStarted();

    Livewire::test(TeamRoster::class, [
        'gameId' => $game->getKey(),
        'team' => TeamAB::TeamA,
        'leftSide' => true,
        'gameState' => $game->stateAt(),
    ])
        ->set('playerOut', '1')
        ->set('playerIn', '7')
        ->call('submitSubstitution')
        ->assertHasNoErrors()
        ->assertDispatched('game-event-recorded');
});

function gameWithActiveSetForTeamRoster(): Game
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
    $game->recordLineup(1, TeamAB::TeamA, [1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5, 6 => 6]);
    $game->recordLineup(1, TeamAB::TeamB, [1 => 11, 2 => 12, 3 => 13, 4 => 14, 5 => 15, 6 => 16]);
    $game->recordSetStarted();

    return $game;
}

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
    $homeCoach = Staff::factory()->for($homeTeam)->create();
    $homeAssistantCoachOne = Staff::factory()->for($homeTeam)->create();
    $homeAssistantCoachTwo = Staff::factory()->for($homeTeam)->create();
    $homeDoctor = Staff::factory()->for($homeTeam)->create();
    $homeTherapist = Staff::factory()->for($homeTeam)->create();
    $awayCoach = Staff::factory()->for($awayTeam)->create();
    $awayAssistantCoachOne = Staff::factory()->for($awayTeam)->create();
    $awayAssistantCoachTwo = Staff::factory()->for($awayTeam)->create();
    $awayDoctor = Staff::factory()->for($awayTeam)->create();
    $awayTherapist = Staff::factory()->for($awayTeam)->create();

    $game->addPlayer($homePlayerOne, number: 12);
    $game->addPlayer($homePlayerTwo, number: 3);
    $game->addPlayer($homeLibero, number: 1, isLibero: true);
    $game->addPlayer($awayPlayerOne, number: 9);
    $game->addPlayer($awayPlayerTwo, number: 2);
    $game->addPlayer($awayLibero, number: 20, isLibero: true);
    $game->addStaff($homeCoach, StaffRole::Coach);
    $game->addStaff($homeAssistantCoachOne, StaffRole::AssistantCoach);
    $game->addStaff($homeAssistantCoachTwo, StaffRole::AssistantCoach);
    $game->addStaff($homeDoctor, StaffRole::Doctor);
    $game->addStaff($homeTherapist, StaffRole::Therapist);
    $game->addStaff($awayCoach, StaffRole::Coach);
    $game->addStaff($awayAssistantCoachOne, StaffRole::AssistantCoach);
    $game->addStaff($awayAssistantCoachTwo, StaffRole::AssistantCoach);
    $game->addStaff($awayDoctor, StaffRole::Doctor);
    $game->addStaff($awayTherapist, StaffRole::Therapist);

    return $game;
}

function submittedLineupState(array $attributes = []): GameState
{
    return GameState::fromAttributes(array_merge([
        'rotation_team_a' => [1 => 999],
        'rotation_team_b' => [1 => 998],
    ], $attributes));
}
