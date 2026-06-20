<?php

declare(strict_types=1);

use App\Enums\OfficialRole;
use App\Models\Game;
use App\Models\Official;
use App\Models\Player;
use App\Models\Staff;
use App\Models\Team;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('database seeder creates exactly one configured match with setup data', function (): void {
    $this->seed(DatabaseSeeder::class);

    $game = Game::query()->with(['homeTeam', 'awayTeam', 'players', 'staff', 'officials'])->sole();
    $homePlayers = $game->players
        ->filter(fn (Player $player): bool => $player->roster->team_id === $game->home_team_id)
        ->values();
    $awayPlayers = $game->players
        ->filter(fn (Player $player): bool => $player->roster->team_id === $game->away_team_id)
        ->values();
    $homeStaff = $game->staff
        ->filter(fn (Staff $staff): bool => $staff->roster->team_id === $game->home_team_id)
        ->values();
    $awayStaff = $game->staff
        ->filter(fn (Staff $staff): bool => $staff->roster->team_id === $game->away_team_id)
        ->values();

    expect(Game::query()->count())->toBe(1)
        ->and(Team::query()->count())->toBe(2)
        ->and(Player::query()->count())->toBe(26)
        ->and(Staff::query()->count())->toBe(10)
        ->and(Official::query()->count())->toBe(count(OfficialRole::cases()))
        ->and($game->homeTeam->name)->toBe('Bologna VC')
        ->and($game->awayTeam->name)->toBe('Paris Volley')
        ->and($game->rosters_submitted)->toBeTrue()
        ->and($homePlayers)->toHaveCount(13)
        ->and($awayPlayers)->toHaveCount(13)
        ->and($homeStaff)->toHaveCount(5)
        ->and($awayStaff)->toHaveCount(5)
        ->and($game->officials)->toHaveCount(count(OfficialRole::cases()));
});

test('database seeder reuses the singleton match when run again', function (): void {
    $this->seed(DatabaseSeeder::class);

    $originalGame = Game::query()->sole();

    $this->seed(DatabaseSeeder::class);

    $game = Game::query()->with(['homeTeam', 'awayTeam', 'players', 'staff', 'officials'])->sole();

    expect(Game::query()->count())->toBe(1)
        ->and($game->getKey())->toBe($originalGame->getKey())
        ->and(Team::query()->count())->toBe(2)
        ->and(Player::query()->count())->toBe(26)
        ->and(Staff::query()->count())->toBe(10)
        ->and(Official::query()->count())->toBe(count(OfficialRole::cases()))
        ->and($game->homeTeam->name)->toBe('Bologna VC')
        ->and($game->awayTeam->name)->toBe('Paris Volley')
        ->and($game->rosters_submitted)->toBeTrue();
});
