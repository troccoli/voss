<?php

use App\Enums\StaffRole;
use App\Models\Game;
use App\Models\Player;
use App\Models\Staff;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a game has match players', function (): void {
    $game = Game::factory()->create();
    $team = Team::factory()->create();
    $player = Player::factory()->for($team)->create();

    $game->addPlayer($player, number: 10);

    expect($game->players)->toHaveCount(1)
        ->and($game->players->first()->getKey())->toBe($player->getKey())
        ->and($game->players->first()->roster->number)->toBe(10);
});

test('can override player number in game', function (): void {
    $game = Game::factory()->create();
    $team = Team::factory()->create();
    $player = Player::factory()->for($team)->create();

    $game->addPlayer($player, number: 99);

    expect($game->players->first()->roster->number)->toBe(99);
});

test('a game has match staff', function (): void {
    $game = Game::factory()->create();
    $team = Team::factory()->create();
    $staff = Staff::factory()->for($team)->create();

    $game->addStaff($staff, StaffRole::Coach);

    expect($game->staff)->toHaveCount(1)
        ->and($game->staff->first()->getKey())->toBe($staff->getKey())
        ->and($game->staff->first()->roster->role)->toBe(StaffRole::Coach);
});

test('can access home and away rosters separately', function (): void {
    $homeTeam = Team::factory()->create();
    $awayTeam = Team::factory()->create();
    $game = Game::factory()->betweenTeams($homeTeam, $awayTeam)->create();

    $homePlayer = Player::factory()->for($homeTeam)->create();
    $awayPlayer = Player::factory()->for($awayTeam)->create();

    $game->addPlayer($homePlayer, number: 10);
    $game->addPlayer($awayPlayer, number: 11);

    expect($game->homePlayers)->toHaveCount(1)
        ->and($game->awayPlayers)->toHaveCount(1)
        ->and($game->homePlayers->first()->getKey())->toBe($homePlayer->getKey())
        ->and($game->awayPlayers->first()->getKey())->toBe($awayPlayer->getKey());
});

test('players are not captains or liberos by default in the player model', function (): void {
    $player = Player::factory()->create();

    // In Eloquent, accessing a missing attribute returns null
    expect($player->is_captain)->toBeNull()
        ->and($player->is_libero)->toBeNull();
});

test('can set captain and libero in game roster', function (): void {
    $game = Game::factory()->create();
    $team = Team::factory()->create();
    $player1 = Player::factory()->for($team)->create();
    $player2 = Player::factory()->for($team)->create();

    $game->addPlayer($player1, number: 10, isCaptain: true);
    $game->addPlayer($player2, number: 11, isLibero: true);

    $freshGame = $game->fresh();

    expect($freshGame->players->firstWhere('id', $player1->getKey())->roster->is_captain)->toBeTrue()
        ->and($freshGame->players->firstWhere('id', $player1->getKey())->roster->is_libero)->toBeFalse()
        ->and($freshGame->players->firstWhere('id', $player2->getKey())->roster->is_libero)->toBeTrue()
        ->and($freshGame->players->firstWhere('id', $player2->getKey())->roster->is_captain)->toBeFalse();
});
