<?php

use App\Enums\OfficialRole;
use App\Models\Game;
use App\Models\Official;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a game can have all the required officials', function () {
    $game = Game::factory()->create();
    $officials = Official::factory()->count(8)->create();

    $roles = OfficialRole::cases();

    foreach ($roles as $index => $role) {
        $game->addOfficial($officials[$index], $role);
    }

    expect($game->officials)->toHaveCount(8);

    $assignedRoles = $game->officials->map(fn (Official $official) => $official->assignment->role);
    foreach ($roles as $role) {
        expect($assignedRoles)->toContain($role);
    }
});
