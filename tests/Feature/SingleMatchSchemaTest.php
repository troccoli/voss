<?php

declare(strict_types=1);

use App\Enums\OfficialRole;
use App\Enums\StaffRole;
use App\Models\Official;
use App\Models\Player;
use App\Models\Staff;
use Illuminate\Support\Facades\Schema;

test('single match schema stores roster and official data directly on match scoped records', function (): void {
    $game = createCurrentMatch();

    expect(Schema::hasColumns('teams', ['game_id', 'side']))->toBeTrue()
        ->and(Schema::hasColumns('players', ['game_id', 'number', 'is_captain', 'is_libero', 'is_rostered']))->toBeTrue()
        ->and(Schema::hasColumns('staff', ['game_id', 'is_rostered']))->toBeTrue()
        ->and(Schema::hasColumns('officials', ['game_id', 'role']))->toBeTrue();

    $player = Player::factory()->for($game->homeTeam)->create();
    $staff = Staff::factory()->for($game->homeTeam)->asCoach()->create();
    $official = Official::factory()->create();

    $game->addPlayer($player, number: 4, isCaptain: true);
    $game->addStaff($staff, StaffRole::Coach);
    $game->addOfficial($official, OfficialRole::FirstReferee);

    $freshPlayer = $player->fresh();
    $freshStaff = $staff->fresh();
    $freshOfficial = $official->fresh();

    expect($freshPlayer?->game_id)->toBe($game->getKey())
        ->and($freshPlayer?->number)->toBe(4)
        ->and($freshPlayer?->is_captain)->toBeTrue()
        ->and($freshPlayer?->is_rostered)->toBeTrue()
        ->and($freshStaff?->game_id)->toBe($game->getKey())
        ->and($freshStaff?->role)->toBe(StaffRole::Coach)
        ->and($freshStaff?->is_rostered)->toBeTrue()
        ->and($freshOfficial?->game_id)->toBe($game->getKey())
        ->and($freshOfficial?->role)->toBe(OfficialRole::FirstReferee);
});
