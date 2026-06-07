<?php

declare(strict_types=1);

use App\Enums\OfficialRole;
use App\Models\Game;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('database seeder leaves the game at the initial roster submission step', function (): void {
    $this->seed(DatabaseSeeder::class);

    $game = Game::query()->with(['players', 'staff', 'officials'])->sole();

    expect($game->rosters_submitted)->toBeFalse()
        ->and($game->players)->toHaveCount(0)
        ->and($game->staff)->toHaveCount(0)
        ->and($game->officials)->toHaveCount(count(OfficialRole::cases()));
});
