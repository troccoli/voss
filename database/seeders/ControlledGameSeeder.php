<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\MatchPhase;
use App\Enums\OfficialRole;
use App\Enums\StaffRole;
use App\Enums\TeamAB;
use App\Enums\TeamSide;
use App\Models\Game;
use App\Models\Official;
use App\Models\Player;
use App\Models\Staff;
use App\Models\Team;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Config;

class ControlledGameSeeder extends Seeder
{
    private const string HOME_TEAM_NAME = 'Dev Home Team';

    private const string HOME_TEAM_COUNTRY = 'ITA';

    private const string AWAY_TEAM_NAME = 'Dev Away Team';

    private const string AWAY_TEAM_COUNTRY = 'FRA';

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
            numbers: range(1, 12),
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
            numbers: range(20, 30),
            liberoNumber: 31,
            staffRoles: [
                StaffRole::Coach,
                StaffRole::AssistantCoach,
                StaffRole::Doctor,
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

        $game->recordToss(TeamSide::Home, TeamAB::TeamA);

        $homeLineup = [1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5, 6 => 6];
        $awayLineup = [1 => 20, 2 => 21, 3 => 22, 4 => 23, 5 => 24, 6 => 25];

        $setWinners = [
            ['winner' => TeamAB::TeamA, 'winner_points' => 25, 'loser_points' => 18],
            ['winner' => TeamAB::TeamB, 'winner_points' => 25, 'loser_points' => 22],
            ['winner' => TeamAB::TeamA, 'winner_points' => 25, 'loser_points' => 19],
            ['winner' => TeamAB::TeamA, 'winner_points' => 25, 'loser_points' => 21],
        ];

        foreach ($setWinners as $index => $set) {
            $setNumber = $index + 1;
            $game->recordLineup($setNumber, TeamAB::TeamA, $homeLineup);
            $game->recordLineup($setNumber, TeamAB::TeamB, $awayLineup);
            $game->recordSetStarted();

            $loser = $set['winner'] === TeamAB::TeamA ? TeamAB::TeamB : TeamAB::TeamA;

            foreach (range(1, $set['loser_points']) as $rally) {
                $game->recordRallyWinner($loser);
            }

            foreach (range(1, $set['winner_points']) as $rally) {
                $game->recordRallyWinner($set['winner']);
            }
        }
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
