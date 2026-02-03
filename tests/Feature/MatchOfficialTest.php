<?php

use App\Enums\OfficialRole;
use App\Models\MatchOfficial;
use App\Models\Official;
use App\Models\VolleyballMatch;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a match can have all the required officials', function () {
    $match = VolleyballMatch::factory()->create();
    $officials = Official::factory()->count(8)->create();

    $roles = OfficialRole::cases();

    foreach ($roles as $index => $role) {
        MatchOfficial::factory()
            ->for($match, 'match')
            ->for($officials[$index], 'official')
            ->withRole($role)
            ->create();
    }

    expect($match->officials)->toHaveCount(8);

    $assignedRoles = $match->officials->pluck('role');
    foreach ($roles as $role) {
        expect($assignedRoles)->toContain($role);
    }
});
