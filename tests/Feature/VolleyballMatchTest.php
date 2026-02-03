<?php

use App\Models\Team;
use App\Models\VolleyballMatch;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a volleyball match has a home team and an away team', function () {
    $homeTeam = Team::factory()->create(['name' => 'Home Team']);
    $awayTeam = Team::factory()->create(['name' => 'Away Team']);

    $match = VolleyballMatch::factory()
        ->betweenTeams($homeTeam, $awayTeam)
        ->create();

    expect($match->home_team_id)->toBe($homeTeam->id)
        ->and($match->away_team_id)->toBe($awayTeam->id)
        ->and($match->homeTeam->id)->toBe($homeTeam->id)
        ->and($match->awayTeam->id)->toBe($awayTeam->id);
});

test('a volleyball match can be created with default factory', function () {
    $match = VolleyballMatch::factory()->create();

    expect($match->homeTeam)->toBeInstanceOf(Team::class)
        ->and($match->awayTeam)->toBeInstanceOf(Team::class);
});

test('a volleyball match has a match date time', function () {
    $dateTime = now()->addDays(1)->startOfSecond();
    $match = VolleyballMatch::factory()->scheduledAt($dateTime)->create();

    expect($match->match_date_time->toDateTimeString())->toBe($dateTime->toDateTimeString());
});

test('a volleyball match has a country code', function () {
    $match = VolleyballMatch::factory()->withCountryCode('USA')->create();

    expect($match->country_code)->toBe('USA');
});
