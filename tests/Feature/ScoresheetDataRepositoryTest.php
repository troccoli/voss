<?php

declare(strict_types=1);

use App\Enums\GameEventType;
use App\Enums\TeamAB;
use App\Enums\TeamSide;
use App\Events\Payloads\TossCompletedPayload;
use App\Models\Game;
use App\Models\Player;
use App\Models\Team;
use App\Services\ScoresheetDataRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('players for side returns non-libero players for each side', function (): void {
    $game = gameWithNumberedRostersForScoresheetDataRepository();
    $repository = app(ScoresheetDataRepository::class);

    $homePlayers = $repository->playersForSide($game, TeamSide::Home);
    $awayPlayers = $repository->playersForSide($game, TeamSide::Away);

    expect(collect($homePlayers)->pluck('number')->all())->toBe([3, 12])
        ->and(collect($homePlayers)->pluck('last_name')->all())->toBe(['Anderson', 'Zephyr'])
        ->and(collect($awayPlayers)->pluck('number')->all())->toBe([2, 9])
        ->and(collect($awayPlayers)->pluck('last_name')->all())->toBe(['Baker', 'Young']);
});

test('players for side reflects roster changes on repeated reads', function (): void {
    $game = gameWithNumberedRostersForScoresheetDataRepository();
    $repository = app(ScoresheetDataRepository::class);

    $playersBeforeRosterChange = $repository->playersForSide($game, TeamSide::Home);

    $newHomePlayer = Player::factory()->for($game->homeTeam)->named('Nina', 'Newest')->create();
    $game->addPlayer($newHomePlayer, number: 8);

    $playersAfterRosterChange = $repository->playersForSide($game, TeamSide::Home);

    expect(collect($playersBeforeRosterChange)->pluck('player_key')->all())->not->toContain($newHomePlayer->getKey())
        ->and(collect($playersAfterRosterChange)->pluck('player_key')->all())->toContain($newHomePlayer->getKey());
});

test('latest toss payload returns the recorded toss payload', function (): void {
    $game = gameWithNumberedRostersForScoresheetDataRepository();
    $game->recordToss(TeamSide::Away, TeamAB::TeamB);

    $repository = app(ScoresheetDataRepository::class);
    $payload = $repository->latestTossPayload($game);

    expect($payload)->not->toBeNull()
        ->and($payload?->teamA)->toBe(TeamSide::Away)
        ->and($payload?->serving)->toBe(TeamAB::TeamB);
});

test('latest toss payload reflects new toss events on repeated reads', function (): void {
    $game = gameWithNumberedRostersForScoresheetDataRepository();
    $game->recordToss(TeamSide::Home, TeamAB::TeamA);

    $repository = app(ScoresheetDataRepository::class);

    $payloadBeforeSecondToss = $repository->latestTossPayload($game);
    $game->events()->create([
        'type' => GameEventType::TossCompleted,
        'payload' => new TossCompletedPayload(
            teamA: TeamSide::Away,
            serving: TeamAB::TeamB,
        ),
    ]);

    $payloadAfterRecordingToss = $repository->latestTossPayload($game);

    expect($payloadBeforeSecondToss)->not->toBeNull()
        ->and($payloadBeforeSecondToss?->teamA)->toBe(TeamSide::Home)
        ->and($payloadBeforeSecondToss?->serving)->toBe(TeamAB::TeamA)
        ->and($payloadAfterRecordingToss?->teamA)->toBe(TeamSide::Away)
        ->and($payloadAfterRecordingToss?->serving)->toBe(TeamAB::TeamB);
});

function gameWithNumberedRostersForScoresheetDataRepository(): Game
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
