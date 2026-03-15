<?php

declare(strict_types=1);

use App\Enums\GameEventType;
use App\Enums\TeamAB;
use App\Enums\TeamSide;
use App\Events\Payloads\TossCompletedPayload;
use App\Models\Game;
use App\Models\Player;
use App\Models\Team;
use App\Services\CacheRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

test('players for side are cached per game and side', function (): void {
    Cache::flush();

    $game = gameWithNumberedRostersForCacheRepository();
    $repository = app(CacheRepository::class);

    $homePlayers = $repository->playersForSide($game, TeamSide::Home);
    $awayPlayers = $repository->playersForSide($game, TeamSide::Away);

    expect(collect($homePlayers)->pluck('number')->all())->toBe([3, 12])
        ->and(collect($homePlayers)->pluck('last_name')->all())->toBe(['Anderson', 'Zephyr'])
        ->and(collect($awayPlayers)->pluck('number')->all())->toBe([2, 9])
        ->and(collect($awayPlayers)->pluck('last_name')->all())->toBe(['Baker', 'Young'])
        ->and(Cache::has(sprintf('game:%d:roster:players-for-side:%s', $game->getKey(), TeamSide::Home->value)))->toBeTrue()
        ->and(Cache::has(sprintf('game:%d:roster:players-for-side:%s', $game->getKey(), TeamSide::Away->value)))->toBeTrue();
});

test('players for side reuses cached value for repeated reads', function (): void {
    Cache::flush();

    $game = gameWithNumberedRostersForCacheRepository();
    $repository = app(CacheRepository::class);

    $cachedPlayers = $repository->playersForSide($game, TeamSide::Home);

    $newHomePlayer = Player::factory()->for($game->homeTeam)->named('Nina', 'Newest')->create();
    $game->addPlayer($newHomePlayer, number: 8);

    $playersAfterRosterChange = $repository->playersForSide($game, TeamSide::Home);

    expect($playersAfterRosterChange)->toBe($cachedPlayers)
        ->and(collect($playersAfterRosterChange)->pluck('player_key')->all())->not->toContain($newHomePlayer->getKey());
});

test('latest toss payload is cached with a game specific key', function (): void {
    Cache::flush();

    $game = gameWithNumberedRostersForCacheRepository();
    $game->recordToss(TeamSide::Away, TeamAB::TeamB);

    $repository = app(CacheRepository::class);
    $payload = $repository->latestTossPayload($game);

    expect($payload)->not->toBeNull()
        ->and($payload?->teamA)->toBe(TeamSide::Away)
        ->and($payload?->serving)->toBe(TeamAB::TeamB)
        ->and(Cache::has(sprintf('game:%d:events:latest-toss-payload', $game->getKey())))->toBeTrue();
});

test('latest toss payload reuses cached value for repeated reads', function (): void {
    Cache::flush();

    $game = gameWithNumberedRostersForCacheRepository();
    $game->recordToss(TeamSide::Home, TeamAB::TeamA);

    $repository = app(CacheRepository::class);

    $cachedPayload = $repository->latestTossPayload($game);
    $game->events()->create([
        'type' => GameEventType::TossCompleted,
        'payload' => new TossCompletedPayload(
            teamA: TeamSide::Away,
            serving: TeamAB::TeamB,
        ),
    ]);

    $payloadAfterRecordingToss = $repository->latestTossPayload($game);

    expect($cachedPayload)->not->toBeNull()
        ->and($cachedPayload?->teamA)->toBe(TeamSide::Home)
        ->and($cachedPayload?->serving)->toBe(TeamAB::TeamA)
        ->and($payloadAfterRecordingToss?->teamA)->toBe(TeamSide::Home)
        ->and($payloadAfterRecordingToss?->serving)->toBe(TeamAB::TeamA);
});

function gameWithNumberedRostersForCacheRepository(): Game
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
