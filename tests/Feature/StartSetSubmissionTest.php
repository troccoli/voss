<?php

declare(strict_types=1);

use App\Enums\GameEventType;
use App\Enums\TeamAB;
use App\Enums\TeamSide;
use App\Events\Payloads\SetStartedPayload;
use App\Livewire\StartSetSubmission;
use App\Models\Game;
use App\Models\GameEvent;
use App\Models\GameStateSnapshot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('game.between_sets_duration', 0);
});

test('start set button is visible only when both team lineups are submitted for the upcoming set', function (): void {
    $game = gameReadyToStartSet();

    $game->recordLineup(1, TeamAB::TeamA, lineupPositionsForNumbers(1));

    Livewire::test(StartSetSubmission::class, ['gameState' => $game->stateAt()])
        ->assertDontSee('Start Game');

    $game->recordLineup(1, TeamAB::TeamB, lineupPositionsForNumbers(11));

    Livewire::test(StartSetSubmission::class, ['gameState' => $game->stateAt()])
        ->assertSee('Start Game');
});

test('start set shows a break countdown after a set ends even when the next lineups are already submitted', function (): void {
    config()->set('game.between_sets_duration', 180);

    $game = gameReadyToStartSet();
    $game->recordLineup(1, TeamAB::TeamA, lineupPositionsForNumbers(1));
    $game->recordLineup(1, TeamAB::TeamB, lineupPositionsForNumbers(11));

    $this->freezeSecond(function () use ($game): void {
        $game->recordSetStarted();

        for ($index = 0; $index < 25; $index++) {
            $game->recordRallyWinner(TeamAB::TeamA);
        }

        $game->recordLineup(2, TeamAB::TeamA, lineupPositionsForNumbers(1));
        $game->recordLineup(2, TeamAB::TeamB, lineupPositionsForNumbers(11));

        Livewire::test(StartSetSubmission::class, ['gameState' => $game->stateAt()])
            ->assertDontSee('Start Set 2')
            ->assertSee('Next set in')
            ->assertSee('03:00')
            ->assertSeeHtml('data-set-break-countdown');
    });
});

test('start set button records set started event and dispatches refresh event', function (): void {
    config()->set('game.between_sets_duration', 0);

    $game = gameReadyToStartSet();
    $game->recordLineup(1, TeamAB::TeamA, lineupPositionsForNumbers(1));
    $game->recordLineup(1, TeamAB::TeamB, lineupPositionsForNumbers(11));

    Livewire::test(StartSetSubmission::class, ['gameState' => $game->stateAt()])
        ->assertSee('Start Game')
        ->call('startSet')
        ->assertHasNoErrors()
        ->assertDispatched('game-event-recorded')
        ->assertDontSee('Start Game');

    $freshGame = $game->fresh();
    $latestEvent = $freshGame->events->last();

    expect($latestEvent)->not->toBeNull()
        ->and($latestEvent->type)->toBe(GameEventType::SetStarted)
        ->and($freshGame->stateAt()->setNumber)->toBe(1)
        ->and($freshGame->stateAt()->setInProgress)->toBeTrue();
});

test('start set cannot be recorded before the break countdown elapses', function (): void {
    config()->set('game.between_sets_duration', 180);

    $game = gameReadyToStartSet();
    $game->recordLineup(1, TeamAB::TeamA, lineupPositionsForNumbers(1));
    $game->recordLineup(1, TeamAB::TeamB, lineupPositionsForNumbers(11));

    $this->freezeSecond(function () use ($game): void {
        $game->recordSetStarted();

        for ($index = 0; $index < 25; $index++) {
            $game->recordRallyWinner(TeamAB::TeamA);
        }

        $game->recordLineup(2, TeamAB::TeamA, lineupPositionsForNumbers(1));
        $game->recordLineup(2, TeamAB::TeamB, lineupPositionsForNumbers(11));

        Livewire::test(StartSetSubmission::class, ['gameState' => $game->stateAt()])
            ->call('startSet')
            ->assertHasErrors(['startSet']);

        expect($game->fresh()->stateAt()->setInProgress)->toBeFalse()
            ->and($game->fresh()->stateAt()->setNumber)->toBe(1);
    });
});

test('start set button is only shown for the opening set', function (): void {
    config()->set('game.between_sets_duration', 0);

    $game = gameReadyToStartSet();
    $game->recordLineup(1, TeamAB::TeamA, lineupPositionsForNumbers(1));
    $game->recordLineup(1, TeamAB::TeamB, lineupPositionsForNumbers(11));
    $game->recordSetStarted();

    for ($i = 0; $i < 25; $i++) {
        $game->recordRallyWinner(TeamAB::TeamA);
    }

    $game->recordLineup(2, TeamAB::TeamA, lineupPositionsForNumbers(1));
    $game->recordLineup(2, TeamAB::TeamB, lineupPositionsForNumbers(11));

    Livewire::test(StartSetSubmission::class, ['gameState' => $game->stateAt()])
        ->assertDontSee('Start Game')
        ->assertSeeHtml('x-init="$wire.startSet()"');
});

test('start set button becomes visible after the break countdown elapses', function (): void {
    config()->set('game.between_sets_duration', 180);

    $game = gameReadyToStartSet();
    $game->recordLineup(1, TeamAB::TeamA, lineupPositionsForNumbers(1));
    $game->recordLineup(1, TeamAB::TeamB, lineupPositionsForNumbers(11));

    $this->freezeSecond(function () use ($game): void {
        $game->recordSetStarted();

        for ($index = 0; $index < 25; $index++) {
            $game->recordRallyWinner(TeamAB::TeamA);
        }

        $game->recordLineup(2, TeamAB::TeamA, lineupPositionsForNumbers(1));
        $game->recordLineup(2, TeamAB::TeamB, lineupPositionsForNumbers(11));

        $this->travel(3)->minutes();

        Livewire::test(StartSetSubmission::class, ['gameState' => $game->stateAt()])
            ->assertDontSee('Start Game')
            ->assertDontSee('Next set in')
            ->assertSeeHtml('x-init="$wire.startSet()"');
    });
});

test('start set button visibility follows snapshot state without querying lineup events', function (): void {
    config()->set('game.between_sets_duration', 0);

    $game = Game::factory()->create();

    $stateEvent = GameEvent::withoutEvents(fn (): GameEvent => GameEvent::query()->create([
        'game_id' => $game->getKey(),
        'type' => GameEventType::SetStarted,
        'payload' => new SetStartedPayload,
        'created_at' => Carbon::now(),
    ]));

    GameStateSnapshot::query()->create([
        'game_id' => $game->getKey(),
        'game_event_id' => $stateEvent->getKey(),
        'set_number' => 0,
        'score_team_a' => 0,
        'score_team_b' => 0,
        'sets_won_team_a' => 0,
        'sets_won_team_b' => 0,
        'timeouts_team_a' => 0,
        'timeouts_team_b' => 0,
        'substitutions_team_a' => 0,
        'substitutions_team_b' => 0,
        'team_a_side' => TeamSide::Home->value,
        'serving_team' => TeamAB::TeamA->value,
        'rotation_team_a' => [1 => 1],
        'rotation_team_b' => [1 => 11],
        'set_in_progress' => false,
        'game_ended' => false,
        'created_at' => Carbon::now(),
    ]);

    Livewire::test(StartSetSubmission::class)
        ->assertSee('Start Game');
});

test('start set button stays hidden before the fifth set toss is submitted', function (): void {
    config()->set('game.between_sets_duration', 0);

    $game = gameReadyToStartSet();

    foreach ([TeamAB::TeamA, TeamAB::TeamB, TeamAB::TeamA, TeamAB::TeamB] as $setWinner) {
        $set = $game->stateAt()->setNumber + 1;
        $game->recordLineup($set, TeamAB::TeamA, lineupPositionsForNumbers(1));
        $game->recordLineup($set, TeamAB::TeamB, lineupPositionsForNumbers(11));
        $game->recordSetStarted();

        for ($index = 0; $index < 25; $index++) {
            $game->recordRallyWinner($setWinner);
        }
    }

    Livewire::test(StartSetSubmission::class, ['gameState' => $game->stateAt()])
        ->assertDontSee('Start Set 5');
});

test('start set shows the break countdown before the fifth set toss is submitted', function (): void {
    config()->set('game.between_sets_duration', 0);

    $game = gameReadyToStartSet();

    foreach ([TeamAB::TeamA, TeamAB::TeamB, TeamAB::TeamA, TeamAB::TeamB] as $setWinner) {
        $set = $game->stateAt()->setNumber + 1;
        $game->recordLineup($set, TeamAB::TeamA, lineupPositionsForNumbers(1));
        $game->recordLineup($set, TeamAB::TeamB, lineupPositionsForNumbers(11));

        $this->freezeSecond(function () use ($game, $setWinner): void {
            $game->recordSetStarted();

            for ($index = 0; $index < 25; $index++) {
                $game->recordRallyWinner($setWinner);
            }
        });
    }

    config()->set('game.between_sets_duration', 180);

    $this->freezeSecond(function () use ($game): void {
        Livewire::test(StartSetSubmission::class, ['gameState' => $game->stateAt()])
            ->assertDontSee('Start Game')
            ->assertSee('Next set in')
            ->assertSeeHtml('data-set-break-countdown');
    });
});

/**
 * @return array<int, int>
 */
function lineupPositionsForNumbers(int $start): array
{
    return standardLineup($start);
}

function gameReadyToStartSet(): Game
{
    $game = makeReadyCurrentMatch();
    $game->recordToss(TeamSide::Home, TeamAB::TeamA);

    return $game;
}
