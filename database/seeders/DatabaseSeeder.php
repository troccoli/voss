<?php

namespace Database\Seeders;

use App\Enums\OfficialRole;
use App\Enums\StaffRole;
use App\Models\Championship;
use App\Models\MatchOfficial;
use App\Models\MatchPlayer;
use App\Models\MatchStaff;
use App\Models\Official;
use App\Models\Player;
use App\Models\Staff;
use App\Models\Team;
use App\Models\User;
use App\Models\VolleyballMatch;
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
        // Create a championship
        $championship = Championship::factory()
            ->create();

        // Create teams and players
        /** @var Team[] $teams */
        $teams = Team::factory()->count(4)->create()->each(function (Team $team) {
            // Create 12 players for each team
            Player::factory()
                ->for($team)
                ->forCountry($team->country_code)
                ->count(12)
                ->create();

            // Create 5 staff for each team
            Staff::factory()
                ->for($team)
                ->forCountry($team->country_code)
                ->count(5)
                ->create();
        });

        // Create officials
        /** @var Collection<int, Official> $officials */
        $officials = Official::factory()->count(10)->create();

        // Create some matches
        $match1 = VolleyballMatch::factory()
            ->for($championship)
            ->betweenTeams($teams[0], $teams[1])
            ->at('Milan', 'Allianz Cloud')
            ->scheduledAt(now()->addDays(1))
            ->create();

        $match2 = VolleyballMatch::factory()
            ->for($championship)
            ->betweenTeams($teams[2], $teams[3])
            ->at('Milan', 'Allianz Cloud')
            ->scheduledAt(now()->addDays(2))
            ->create();

        // Assign officials, players and staff to matches
        /** @var VolleyballMatch $match */
        foreach ([$match1, $match2] as $match) {
            // Officials
            $roles = OfficialRole::cases();
            /** @var Collection<int, Official> $shuffledOfficials */
            $shuffledOfficials = $officials
                ->reject(fn (Official $official) => $official->country_code === $match->homeTeam->country_code)
                ->reject(fn (Official $official) => $official->country_code === $match->awayTeam->country_code)
                ->shuffle();
            foreach ($roles as $index => $role) {
                /** @var Official $official */
                $official = $shuffledOfficials[$index];
                MatchOfficial::factory()
                    ->for($match, 'match')
                    ->for($official, 'official')
                    ->withRole($role)
                    ->create();
            }

            // Players and Staff for both teams
            foreach ([$match->homeTeam, $match->awayTeam] as $team) {
                foreach ($team->players as $index => $player) {
                    MatchPlayer::factory()->create([
                        'volleyball_match_id' => $match->id,
                        'player_id' => $player->id,
                        'team_id' => $team->id,
                        'number' => $player->number,
                        'is_captain' => $index === 0,
                        'is_libero' => $index >= 10,
                    ]);
                }

                $staffRoles = [
                    StaffRole::Coach,
                    StaffRole::AssistantCoach,
                    StaffRole::AssistantCoach,
                    StaffRole::Therapist,
                    StaffRole::Doctor,
                ];
                foreach ($team->staff as $index => $staff) {
                    MatchStaff::factory()->create([
                        'volleyball_match_id' => $match->id,
                        'staff_id' => $staff->id,
                        'team_id' => $team->id,
                        'role' => $staffRoles[$index] ?? StaffRole::Coach,
                    ]);
                }
            }
        }

        User::factory()
            ->testUser()
            ->create();
    }
}
