<?php

declare(strict_types=1);

use App\Data\GameState\GameState;
use App\Enums\GameEventType;
use App\Enums\TeamAB;
use App\Enums\TeamSide;
use App\Livewire\LineupSubmission;
use App\Models\Game;
use App\Models\Player;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\View\ViewException;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/**
 * @return array<int, string>
 */
function validLineupInput(): array
{
    return [
        1 => '1',
        2 => '2',
        3 => '3',
        4 => '4',
        5 => '5',
        6 => '6',
    ];
}

/**
 * @return array<int, int>
 */
function validLineupPositions(): array
{
    return [
        1 => 1,
        2 => 2,
        3 => 3,
        4 => 4,
        5 => 5,
        6 => 6,
    ];
}

function prepareGameForLineupSubmission(): Game
{
    $homeTeam = Team::factory()->create();
    $awayTeam = Team::factory()->create();
    $game = Game::factory()->betweenTeams($homeTeam, $awayTeam)->create();

    $homePlayers = Player::factory()->for($homeTeam)->count(6)->create();
    foreach ($homePlayers as $index => $player) {
        $game->addPlayer($player, number: $index + 1);
    }

    $homeLibero = Player::factory()->for($homeTeam)->create();
    $game->addPlayer($homeLibero, number: 99, isLibero: true);

    $awayPlayers = Player::factory()->for($awayTeam)->count(6)->create();
    foreach ($awayPlayers as $index => $player) {
        $game->addPlayer($player, number: $index + 11);
    }

    $game->recordToss(TeamSide::Home, TeamAB::TeamA);

    return $game;
}

test('lineup submission is hidden before toss is submitted', function (): void {
    $game = Game::factory()->create();

    Livewire::test(LineupSubmission::class, ['team' => TeamAB::TeamA, 'gameId' => $game->getKey()])
        ->assertDontSee('Submit Lineup')
        ->assertDontSee('Team A Lineup');
});

test('lineup submission renders team a button and modal after toss is submitted', function (): void {
    $game = prepareGameForLineupSubmission();

    Livewire::test(LineupSubmission::class, ['team' => TeamAB::TeamA, 'gameId' => $game->getKey()])
        ->assertSee('Submit Lineup')
        ->assertSee('Team A Lineup')
        ->assertSeeHtml('submit-lineup-team_a')
        ->assertSeeHtml('name="lineup[1]"')
        ->assertSeeHtml('autofocus')
        ->assertSeeHtml('name="lineup[6]"')
        ->assertSeeHtml('data-lineup-roster-numbers')
        ->assertSeeHtml('data-lineup-roster-number="1"')
        ->assertSeeHtml('data-lineup-roster-number="6"')
        ->assertDontSeeHtml('data-lineup-roster-number="99"')
        ->assertDontSeeHtml('data-lineup-roster-number="11"')
        ->assertSee('Submit');
});

test('lineup submission renders team b button and modal after toss is submitted', function (): void {
    $game = prepareGameForLineupSubmission();

    Livewire::test(LineupSubmission::class, ['team' => TeamAB::TeamB, 'gameId' => $game->getKey()])
        ->assertSee('Submit Lineup')
        ->assertSee('Team B Lineup')
        ->assertSeeHtml('submit-lineup-team_b')
        ->assertSeeHtml('name="lineup[1]"')
        ->assertSeeHtml('name="lineup[6]"')
        ->assertSeeHtml('data-lineup-roster-numbers')
        ->assertSeeHtml('data-lineup-roster-number="11"')
        ->assertSeeHtml('data-lineup-roster-number="16"')
        ->assertDontSeeHtml('data-lineup-roster-number="99"')
        ->assertDontSeeHtml('data-lineup-roster-number="1"')
        ->assertSee('Submit');
});

test('lineup submission rejects unsupported team value', function (): void {
    $game = Game::factory()->create();

    expect(fn (): Testable => Livewire::test(LineupSubmission::class, ['team' => 'invalid', 'gameId' => $game->getKey()]))
        ->toThrow(ViewException::class);
});

test('lineup submission records an event and dispatches a refresh event', function (): void {
    $game = prepareGameForLineupSubmission();

    Livewire::test(LineupSubmission::class, ['team' => TeamAB::TeamA, 'gameId' => $game->getKey()])
        ->set('lineup', validLineupInput())
        ->call('submit')
        ->assertHasNoErrors()
        ->assertDispatched('game-event-recorded')
        ->assertSet('lineup.1', '');

    $lineupEvent = $game->fresh()->events->last();

    expect($lineupEvent)->not->toBeNull()
        ->and($lineupEvent->type)->toBe(GameEventType::LineupSubmitted)
        ->and($lineupEvent->payload->positions)->toBe([
            1 => 1,
            2 => 2,
            3 => 3,
            4 => 4,
            5 => 5,
            6 => 6,
        ]);
});

test('lineup submission requires positive integers', function (): void {
    $game = prepareGameForLineupSubmission();
    $lineup = validLineupInput();
    $lineup[1] = '0';

    Livewire::test(LineupSubmission::class, ['team' => TeamAB::TeamA, 'gameId' => $game->getKey()])
        ->set('lineup', $lineup)
        ->call('submit')
        ->assertHasErrors(['submit'])
        ->assertHasNoErrors(['lineup.1', 'lineup.2', 'lineup.3', 'lineup.4', 'lineup.5', 'lineup.6']);
});

test('lineup submission requires all roster numbers to be different', function (): void {
    $game = prepareGameForLineupSubmission();
    $lineup = validLineupInput();
    $lineup[2] = '1';

    Livewire::test(LineupSubmission::class, ['team' => TeamAB::TeamA, 'gameId' => $game->getKey()])
        ->set('lineup', $lineup)
        ->call('submit')
        ->assertHasErrors(['submit'])
        ->assertHasNoErrors(['lineup.1', 'lineup.2', 'lineup.3', 'lineup.4', 'lineup.5', 'lineup.6']);

    expect($game->fresh()->events)->toHaveCount(1);
});

test('lineup submission rejects roster numbers not eligible for the selected team', function (string $invalidRosterNumber): void {
    $game = prepareGameForLineupSubmission();
    $lineup = validLineupInput();
    $lineup[1] = $invalidRosterNumber;

    Livewire::test(LineupSubmission::class, ['team' => TeamAB::TeamA, 'gameId' => $game->getKey()])
        ->set('lineup', $lineup)
        ->call('submit')
        ->assertHasErrors(['submit'])
        ->assertHasNoErrors(['lineup.1', 'lineup.2', 'lineup.3', 'lineup.4', 'lineup.5', 'lineup.6']);

    expect($game->fresh()->events)->toHaveCount(1);
})->with([
    'away team roster number' => ['11'],
    'libero roster number' => ['99'],
]);

test('lineup submission is aware of the injected game context', function (): void {
    $game = Game::factory()->create();

    Livewire::test(LineupSubmission::class, [
        'team' => TeamAB::TeamA,
        'gameId' => $game->getKey(),
        'gameState' => GameState::fromAttributes([
            'set_number' => 2,
            'serving_team' => TeamAB::TeamB->value,
        ]),
    ])
        ->assertSet('gameId', $game->getKey())
        ->assertSet('gameState', fn (GameState $gameState): bool => $gameState->setNumber === 2
            && $gameState->servingTeam === TeamAB::TeamB);
});

test('lineup submission button is hidden after the lineup is already submitted for the same team and upcoming set', function (): void {
    $game = prepareGameForLineupSubmission();
    $game->recordLineup(1, TeamAB::TeamA, validLineupPositions());

    Livewire::test(LineupSubmission::class, [
        'team' => TeamAB::TeamA,
        'gameId' => $game->getKey(),
        'gameState' => $game->stateAt(),
    ])
        ->assertDontSee('Submit Lineup')
        ->assertDontSee('Team A Lineup');
});

test('lineup submission button remains visible for the other team when only one team has submitted', function (): void {
    $game = prepareGameForLineupSubmission();
    $game->recordLineup(1, TeamAB::TeamA, validLineupPositions());

    Livewire::test(LineupSubmission::class, [
        'team' => TeamAB::TeamB,
        'gameId' => $game->getKey(),
        'gameState' => $game->stateAt(),
    ])
        ->assertSee('Submit Lineup')
        ->assertSee('Team B Lineup');
});
