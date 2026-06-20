<?php

declare(strict_types=1);

use App\Enums\OfficialRole;
use App\Enums\StaffRole;
use App\Models\Game;
use App\Models\GameStateSnapshot;
use App\Models\Player;
use App\Models\Staff;
use App\Models\Team;
use Database\Seeders\ControlledGameSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('controlled game seeder creates one deterministic completed match', function (): void {
    config()->set('game.between_sets_duration', 180);

    $this->seed(ControlledGameSeeder::class);

    $game = Game::query()->with(['homeTeam', 'awayTeam', 'players', 'staff', 'officials'])->sole();
    $latestSnapshot = GameStateSnapshot::query()
        ->where('game_id', $game->getKey())
        ->latest('game_event_id')
        ->firstOrFail();
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
        ->and($game->homeTeam->name)->toBe('Dev Home Team')
        ->and($game->awayTeam->name)->toBe('Dev Away Team')
        ->and($homePlayers)->toHaveCount(13)
        ->and($awayPlayers)->toHaveCount(12)
        ->and($game->officials)->toHaveCount(count(OfficialRole::cases()))
        ->and($latestSnapshot->game_ended)->toBeTrue()
        ->and($latestSnapshot->sets_won_team_a)->toBe(3)
        ->and($latestSnapshot->sets_won_team_b)->toBe(1);

    $homeRosterNumbers = $homePlayers
        ->sortBy(fn (Player $player): int => $player->roster->number)
        ->pluck('roster.number')
        ->values()
        ->all();

    $awayRosterNumbers = $awayPlayers
        ->sortBy(fn (Player $player): int => $player->roster->number)
        ->pluck('roster.number')
        ->values()
        ->all();

    expect($homeRosterNumbers)->toBe(range(1, 13))
        ->and($awayRosterNumbers)->toBe(range(20, 31))
        ->and($homePlayers->filter(fn (Player $player): bool => $player->roster->is_libero)->count())->toBe(1)
        ->and($awayPlayers->filter(fn (Player $player): bool => $player->roster->is_libero)->count())->toBe(1);

    expect($homeStaff)->toHaveCount(5)
        ->and($awayStaff)->toHaveCount(3);

    $homeRoleCounts = $homeStaff
        ->countBy(fn (Staff $staff): string => $staff->roster->role->value)
        ->all();

    $awayRoleCounts = $awayStaff
        ->countBy(fn (Staff $staff): string => $staff->roster->role->value)
        ->all();

    expect($homeRoleCounts[StaffRole::Coach->value] ?? 0)->toBe(1)
        ->and($homeRoleCounts[StaffRole::AssistantCoach->value] ?? 0)->toBe(2)
        ->and($homeRoleCounts[StaffRole::Doctor->value] ?? 0)->toBe(1)
        ->and($homeRoleCounts[StaffRole::Therapist->value] ?? 0)->toBe(1);

    expect($awayRoleCounts[StaffRole::Coach->value] ?? 0)->toBe(1)
        ->and($awayRoleCounts[StaffRole::AssistantCoach->value] ?? 0)->toBe(1)
        ->and($awayRoleCounts[StaffRole::Doctor->value] ?? 0)->toBe(1)
        ->and($awayRoleCounts[StaffRole::Therapist->value] ?? 0)->toBe(0);
});

test('controlled game seeder restores the configured between-sets interval after seeding', function (): void {
    config()->set('game.between_sets_duration', 180);

    $this->seed(ControlledGameSeeder::class);

    expect(config('game.between_sets_duration'))->toBe(180);
});

test('controlled game seeder reuses the singleton match when run again', function (): void {
    $this->seed(ControlledGameSeeder::class);

    $originalGame = Game::query()->sole();

    $this->seed(ControlledGameSeeder::class);

    $game = Game::query()->with(['homeTeam', 'awayTeam', 'players', 'staff', 'officials'])->sole();
    $latestSnapshot = GameStateSnapshot::query()
        ->where('game_id', $game->getKey())
        ->latest('game_event_id')
        ->firstOrFail();

    expect(Game::query()->count())->toBe(1)
        ->and($game->getKey())->toBe($originalGame->getKey())
        ->and(Team::query()->count())->toBe(2)
        ->and($game->players)->toHaveCount(25)
        ->and($game->staff)->toHaveCount(8)
        ->and($game->officials)->toHaveCount(count(OfficialRole::cases()))
        ->and($latestSnapshot->game_ended)->toBeTrue()
        ->and($latestSnapshot->sets_won_team_a)->toBe(3)
        ->and($latestSnapshot->sets_won_team_b)->toBe(1);
});
