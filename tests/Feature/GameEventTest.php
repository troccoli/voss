<?php

declare(strict_types=1);

use App\Enums\GameEventType;
use App\Enums\MatchPhase;
use App\Enums\TeamAB;
use App\Enums\TeamSide;
use App\Events\Payloads\LineupSubmittedPayload;
use App\Events\Payloads\TossCompletedPayload;
use App\Exceptions\InvalidGameEventTransition;
use App\Models\Game;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('game.between_sets_duration', 0);
});

test('singleton match records the opening toss on the current game', function (): void {
    makeReadyCurrentMatch();

    Game::current()->recordToss(TeamSide::Home, TeamAB::TeamA);

    $game = Game::current()->fresh();
    $event = $game->events->sole();

    expect($game->status)->toBe(MatchPhase::InProgress)
        ->and($event->type)->toBe(GameEventType::TossCompleted)
        ->and($event->payload)->toBeInstanceOf(TossCompletedPayload::class)
        ->and($event->payload->teamA)->toBe(TeamSide::Home)
        ->and($event->payload->serving)->toBe(TeamAB::TeamA);
});

test('singleton match records lineup submission set start and rally progression in order', function (): void {
    $game = makeReadyCurrentMatch();

    $game->recordToss(TeamSide::Home, TeamAB::TeamA);
    $game->recordLineup(1, TeamAB::TeamA, standardLineup());
    $game->recordLineup(1, TeamAB::TeamB, standardLineup(11));
    $game->recordSetStarted();
    $game->recordRallyWinner(TeamAB::TeamA);

    $freshGame = $game->fresh();
    $events = $freshGame->events->values();
    $lineupEvent = $events[1];

    expect($events->pluck('type')->all())->toBe([
        GameEventType::TossCompleted,
        GameEventType::LineupSubmitted,
        GameEventType::LineupSubmitted,
        GameEventType::SetStarted,
        GameEventType::RallyEnded,
    ])
        ->and($lineupEvent->payload)->toBeInstanceOf(LineupSubmittedPayload::class)
        ->and($lineupEvent->payload->positions)->toBe(standardLineup())
        ->and($freshGame->stateAt()->setInProgress)->toBeTrue()
        ->and($freshGame->stateAt()->scoreTeamA)->toBe(1)
        ->and($freshGame->stateAt()->scoreTeamB)->toBe(0);
});

test('singleton match rejects lineup submission before the opening toss', function (): void {
    $game = makeReadyCurrentMatch();

    expect(fn () => $game->recordLineup(1, TeamAB::TeamA, standardLineup()))
        ->toThrow(InvalidGameEventTransition::class);
});
