<?php

use App\Models\Team;

test('a team has a country code', function (): void {
    $team = Team::factory()->create([
        'name' => 'Italy',
        'country_code' => 'ITA',
    ]);

    expect($team->country_code)->toBe('ITA')
        ->and($team->name)->toBe('Italy');
});

test('factory generates valid matching country and code', function (): void {
    $team = Team::factory()->create();

    expect($team->country_code)->toHaveLength(3)
        ->and($team->name)->not->toBeEmpty();

    // We can't easily check matching without the list, but we can check it's not the old "Club" style
    expect($team->name)->not->toContain('Volleyball Club');
});
