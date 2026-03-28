<?php

declare(strict_types=1);

use App\Data\GameState\GameState;
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

test('team roster renders staff role circles in the requested order for team a', function (): void {
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

test('team roster renders staff role circles left to right for team b', function (): void {
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
