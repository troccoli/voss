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
use Carbon\CarbonImmutable;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ControlledGameSeeder extends Seeder
{
    use WithoutModelEvents;

    private const string HOME_TEAM_NAME = 'Dev Home Team';

    private const string AWAY_TEAM_NAME = 'Dev Away Team';

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $championship = Championship::factory()->named('Dev Controlled Championship')->create();

        $homeTeam = Team::factory()->create([
            'name' => self::HOME_TEAM_NAME,
            'country_code' => 'ITA',
        ]);

        $awayTeam = Team::factory()->create([
            'name' => self::AWAY_TEAM_NAME,
            'country_code' => 'FRA',
        ]);

        Player::factory()
            ->for($homeTeam)
            ->forCountry($homeTeam->country_code)
            ->count(13)
            ->create();

        Player::factory()
            ->for($awayTeam)
            ->forCountry($awayTeam->country_code)
            ->count(12)
            ->create();

        Staff::factory()
            ->for($homeTeam)
            ->forCountry($homeTeam->country_code)
            ->count(5)
            ->create();

        Staff::factory()
            ->for($awayTeam)
            ->forCountry($awayTeam->country_code)
            ->count(3)
            ->create();

        $game = Game::factory()
            ->for($championship, 'championship')
            ->betweenTeams($homeTeam, $awayTeam)
            ->withMatchNumber(1)
            ->withCountryCode($homeTeam->country_code)
            ->at('Bologna', 'PalaDozza')
            ->scheduledAt(CarbonImmutable::now()->addDay()->setTime(20, 30))
            ->create();

        foreach (OfficialRole::cases() as $role) {
            $game->addOfficial(Official::factory()->create(), $role);
        }

        $homeNumbers = range(1, 13);
        $awayNumbers = range(20, 31);

        foreach ($homeTeam->players()->orderBy('id')->get() as $index => $player) {
            $number = $homeNumbers[$index];

            $game->addPlayer(
                player: $player,
                number: $number,
                isCaptain: $index === 0,
                isLibero: $number === 13,
            );
        }

        foreach ($awayTeam->players()->orderBy('id')->get() as $index => $player) {
            $game->addPlayer(
                player: $player,
                number: $awayNumbers[$index],
                isCaptain: $index === 0,
                isLibero: false,
            );
        }

        $homeStaffRoles = [
            StaffRole::Coach,
            StaffRole::AssistantCoach,
            StaffRole::AssistantCoach,
            StaffRole::Doctor,
            StaffRole::Therapist,
        ];

        foreach ($homeTeam->staff()->orderBy('id')->get() as $index => $staff) {
            $game->addStaff($staff, $homeStaffRoles[$index]);
        }

        $awayStaffRoles = [
            StaffRole::Coach,
            StaffRole::AssistantCoach,
            StaffRole::Doctor,
        ];

        foreach ($awayTeam->staff()->orderBy('id')->get() as $index => $staff) {
            $game->addStaff($staff, $awayStaffRoles[$index]);
        }
    }
}
