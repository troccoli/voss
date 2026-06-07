<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\OfficialRole;
use App\Enums\TeamAB;
use App\Enums\TeamSide;
use App\Models\Championship;
use App\Models\Game;
use App\Models\Official;
use App\Models\Player;
use App\Models\Staff;
use App\Models\Team;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Config;

class ControlledGameSeeder extends Seeder
{
    private const string HOME_TEAM_NAME = 'Dev Home Team';

    private const string AWAY_TEAM_NAME = 'Dev Away Team';

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $betweenSetsDuration = Config::integer('game.between_sets_duration');
        Config::set('game.between_sets_duration', 0);

        try {
            $this->seedControlledGame();
        } finally {
            Config::set('game.between_sets_duration', $betweenSetsDuration);
        }
    }

    private function seedControlledGame(): void
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

        $homeStaff = [
            Staff::factory()->for($homeTeam)->forCountry($homeTeam->country_code)->asCoach()->create(),
            Staff::factory()->for($homeTeam)->forCountry($homeTeam->country_code)->asAssistantCoach()->create(),
            Staff::factory()->for($homeTeam)->forCountry($homeTeam->country_code)->asAssistantCoach()->create(),
            Staff::factory()->for($homeTeam)->forCountry($homeTeam->country_code)->asDoctor()->create(),
            Staff::factory()->for($homeTeam)->forCountry($homeTeam->country_code)->asTherapist()->create(),
        ];

        $awayStaff = [
            Staff::factory()->for($awayTeam)->forCountry($awayTeam->country_code)->asCoach()->create(),
            Staff::factory()->for($awayTeam)->forCountry($awayTeam->country_code)->asAssistantCoach()->create(),
            Staff::factory()->for($awayTeam)->forCountry($awayTeam->country_code)->asDoctor()->create(),
        ];

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

        foreach ($homeStaff as $staff) {
            $game->addStaff($staff);
        }

        foreach ($awayStaff as $staff) {
            $game->addStaff($staff);
        }

        $game->recordToss(TeamSide::Home, TeamAB::TeamA);

        $homeLineup = [1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5, 6 => 6];
        $awayLineup = [1 => 20, 2 => 21, 3 => 22, 4 => 23, 5 => 24, 6 => 25];

        $setWinners = [TeamAB::TeamA, TeamAB::TeamB, TeamAB::TeamA, TeamAB::TeamB];

        foreach ($setWinners as $index => $winner) {
            $setNumber = $index + 1;
            $game->recordLineup($setNumber, TeamAB::TeamA, $homeLineup);
            $game->recordLineup($setNumber, TeamAB::TeamB, $awayLineup);
            $game->recordSetStarted();

            for ($i = 0; $i < 25; $i++) {
                $game->recordRallyWinner($winner);
            }
        }
    }
}
