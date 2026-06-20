<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\MatchPhase;
use App\Enums\OfficialRole;
use App\Enums\StaffRole;
use App\Models\Game;
use App\Models\Official;
use App\Models\Player;
use App\Models\Staff;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    private const string HOME_TEAM_NAME = 'Bologna VC';

    private const string HOME_TEAM_COUNTRY = 'ITA';

    private const string AWAY_TEAM_NAME = 'Paris Volley';

    private const string AWAY_TEAM_COUNTRY = 'FRA';

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->seedConfiguredMatch();

        User::query()->firstOrCreate(
            ['email' => 'test@example.com'],
            User::factory()->testUser()->make()->getAttributes(),
        );
    }

    private function seedConfiguredMatch(): Game
    {
        $game = Game::ensureSingleton(
            gameAttributes: [
                'number' => 1,
                'country_code' => self::HOME_TEAM_COUNTRY,
                'city' => 'Bologna',
                'hall' => 'PalaDozza',
                'date_time' => now()->addDay()->setTime(20, 30),
                'division' => 'Men',
                'pool' => 'A',
                'category' => 'Senior',
                'status' => MatchPhase::Setup,
            ],
            homeTeamAttributes: [
                'name' => self::HOME_TEAM_NAME,
                'country_code' => self::HOME_TEAM_COUNTRY,
            ],
            awayTeamAttributes: [
                'name' => self::AWAY_TEAM_NAME,
                'country_code' => self::AWAY_TEAM_COUNTRY,
            ],
        );

        $game->resetForSetup();
        $game->load(['homeTeam', 'awayTeam', 'officials']);

        $this->seedRoster(
            game: $game,
            team: $game->homeTeam,
            numbers: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12],
            liberoNumber: 13,
            staffRoles: [
                StaffRole::Coach,
                StaffRole::AssistantCoach,
                StaffRole::AssistantCoach,
                StaffRole::Doctor,
                StaffRole::Therapist,
            ],
        );

        $this->seedRoster(
            game: $game,
            team: $game->awayTeam,
            numbers: [2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 14],
            liberoNumber: 15,
            staffRoles: [
                StaffRole::Coach,
                StaffRole::AssistantCoach,
                StaffRole::AssistantCoach,
                StaffRole::Doctor,
                StaffRole::Therapist,
            ],
        );

        foreach ($this->officialAssignments() as $assignment) {
            Official::factory()
                ->named($assignment['first_name'], $assignment['last_name'])
                ->withCountryCode($assignment['country_code'])
                ->forMatch($game)
                ->withRole($assignment['role'])
                ->create();
        }

        $game->markRostersSubmitted();

        return $game;
    }

    /**
     * @param  array<int, int>  $numbers
     * @param  array<int, StaffRole>  $staffRoles
     */
    private function seedRoster(Game $game, Team $team, array $numbers, int $liberoNumber, array $staffRoles): void
    {
        foreach ($numbers as $index => $number) {
            $playerFactory = Player::factory()
                ->for($team)
                ->forMatch($game)
                ->forCountry($team->country_code)
                ->withNumber($number)
                ->rostered();

            if ($index === 0) {
                $playerFactory = $playerFactory->asCaptain();
            }

            $playerFactory->create();
        }

        Player::factory()
            ->for($team)
            ->forMatch($game)
            ->forCountry($team->country_code)
            ->withNumber($liberoNumber)
            ->asLibero()
            ->rostered()
            ->create();

        foreach ($staffRoles as $role) {
            Staff::factory()
                ->for($team)
                ->forMatch($game)
                ->forCountry($team->country_code)
                ->withRole($role)
                ->rostered()
                ->create();
        }
    }

    /**
     * @return array<int, array{role: OfficialRole, first_name: string, last_name: string, country_code: string}>
     */
    private function officialAssignments(): array
    {
        return [
            ['role' => OfficialRole::FirstReferee, 'first_name' => 'Marta', 'last_name' => 'Silva', 'country_code' => 'POR'],
            ['role' => OfficialRole::SecondReferee, 'first_name' => 'Lukas', 'last_name' => 'Meyer', 'country_code' => 'GER'],
            ['role' => OfficialRole::Scorer, 'first_name' => 'Ana', 'last_name' => 'Lopez', 'country_code' => 'ESP'],
            ['role' => OfficialRole::AssistantScorer, 'first_name' => 'Sophie', 'last_name' => 'Martin', 'country_code' => 'BEL'],
            ['role' => OfficialRole::LineJudge1, 'first_name' => 'Klara', 'last_name' => 'Novak', 'country_code' => 'CZE'],
            ['role' => OfficialRole::LineJudge2, 'first_name' => 'Milan', 'last_name' => 'Kovac', 'country_code' => 'SRB'],
            ['role' => OfficialRole::LineJudge3, 'first_name' => 'Elin', 'last_name' => 'Berg', 'country_code' => 'SWE'],
            ['role' => OfficialRole::LineJudge4, 'first_name' => 'Noah', 'last_name' => 'Van Dijk', 'country_code' => 'NED'],
        ];
    }
}
