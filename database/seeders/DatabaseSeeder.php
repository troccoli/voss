<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\OfficialRole;
use App\Enums\StaffRole;
use App\Models\Championship;
use App\Models\Game;
use App\Models\Official;
use App\Models\Player;
use App\Models\Staff;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        /** @var Championship $championship */
        $championship = Championship::factory()->create();

        // Create teams and players
        /** @var Team[] $teams */
        $teams = Team::factory()->count(2)->create()->each(function (Team $team) {
            Player::factory()
                ->for($team)
                ->forCountry($team->country_code)
                ->count(14)
                ->create();

            Staff::factory()
                ->for($team)
                ->forCountry($team->country_code)
                ->count(5)
                ->create();
        });

        /** @var Collection<int, Official> $officials */
        $officials = Official::factory()->count(8)->create();

        /** @var Game $game */
        $game = Game::factory()
            ->for($championship, 'championship')
            ->betweenTeams($teams[0], $teams[1])
            ->scheduledAt(now()->addDays(1))
            ->create();

        // Assign officials, players and staff to matches
        $roles = OfficialRole::cases();
        /** @var Collection<int, Official> $shuffledOfficials */
        $shuffledOfficials = $officials
            ->reject(fn (Official $official) => $official->country_code === $game->homeTeam->country_code)
            ->reject(fn (Official $official) => $official->country_code === $game->awayTeam->country_code)
            ->shuffle();
        foreach ($roles as $index => $role) {
            /** @var Official $official */
            $official = $shuffledOfficials[$index];
            $game->addOfficial($official, $role);
        }

        // Players and Staff for both teams
        foreach ([$game->homeTeam, $game->awayTeam] as $team) {
            $usedNumbers = [];
            /** @var Player $player */
            $captain = mt_rand(1, 14);
            do {
                $libero1 = mt_rand(1, 14);
                $libero2 = mt_rand(1, 14);
            } while ($captain === $libero1 || $captain === $libero2 || $libero1 === $libero2);
            foreach ($team->players as $index => $player) {
                $playerNumber = fake()->unique()->numberBetween(1, 99);
                $game->addPlayer(
                    player: $player,
                    number: $playerNumber,
                    isCaptain: $index === $captain,
                    isLibero: $index === $libero1 || $index === $libero2
                );
            }

            $staffRoles = [
                StaffRole::Coach,
                StaffRole::AssistantCoach,
                StaffRole::AssistantCoach,
                StaffRole::Therapist,
                StaffRole::Doctor,
            ];
            foreach ($team->staff as $index => $staff) {
                $game->addStaff(staff: $staff, role: $staffRoles[$index]);
            }
        }

        User::factory()
            ->testUser()
            ->create();
    }
}
