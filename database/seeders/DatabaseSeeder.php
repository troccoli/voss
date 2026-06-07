<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\OfficialRole;
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

            Staff::factory()->for($team)->forCountry($team->country_code)->asCoach()->create();
            Staff::factory()->for($team)->forCountry($team->country_code)->asAssistantCoach()->create();
            Staff::factory()->for($team)->forCountry($team->country_code)->asAssistantCoach()->create();
            Staff::factory()->for($team)->forCountry($team->country_code)->asTherapist()->create();
            Staff::factory()->for($team)->forCountry($team->country_code)->asDoctor()->create();
        });

        /** @var Collection<int, Official> $officials */
        $officials = Official::factory()->count(8)->create();

        /** @var Game $game */
        $game = Game::factory()
            ->for($championship, 'championship')
            ->betweenTeams($teams[0], $teams[1])
            ->scheduledAt(now()->addDays(1))
            ->create();

        // Assign officials to the match. Leave rosters empty so the UI starts at roster submission.
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

        User::factory()
            ->testUser()
            ->create();
    }
}
