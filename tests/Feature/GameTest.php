<?php

use App\Models\Game;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a game has a home team and an away team', function () {
    $homeTeam = Team::factory()->create(['name' => 'Home Team']);
    $awayTeam = Team::factory()->create(['name' => 'Away Team']);

    $game = Game::factory()
        ->betweenTeams($homeTeam, $awayTeam)
        ->create();

    expect($game->home_team_id)->toBe($homeTeam->getKey())
        ->and($game->away_team_id)->toBe($awayTeam->getKey())
        ->and($game->homeTeam->getKey())->toBe($homeTeam->getKey())
        ->and($game->awayTeam->getKey())->toBe($awayTeam->getKey());
});

test('a game can be created with default factory', function () {
    $game = Game::factory()->create();

    expect($game->homeTeam)->toBeInstanceOf(Team::class)
        ->and($game->awayTeam)->toBeInstanceOf(Team::class);
});

test('a game has a match date time', function () {
    $dateTime = now()->addDays(1)->startOfSecond();
    $game = Game::factory()->scheduledAt($dateTime)->create();

    expect($game->date_time->toDateTimeString())->toBe($dateTime->toDateTimeString());
});

test('a game has a country code', function () {
    $game = Game::factory()->withCountryCode('USA')->create();

    expect($game->country_code)->toBe('USA');
});

test('a game factory generates a 3-letter country code by default', function () {
    $game = Game::factory()->create();

    expect(strlen($game->country_code))->toBe(3);
});

test('a game has a match number between 1 and 99', function () {
    $game = Game::factory()->withMatchNumber(42)->create();

    expect($game->number)->toBe(42)
        ->and($game->number)->toBeGreaterThanOrEqual(1)
        ->and($game->number)->toBeLessThanOrEqual(99);
});

test('match number is cast to integer', function () {
    $game = Game::factory()->create(['number' => '50']);

    expect($game->number)->toBeInt()
        ->toBe(50);
});
