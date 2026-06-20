<?php

declare(strict_types=1);

use App\Enums\MatchPhase;
use App\Enums\OfficialRole;
use App\Enums\TeamAB;
use App\Enums\TeamSide;
use App\Models\Game;
use App\Models\Official;
use App\Models\Player;
use App\Models\Staff;
use Illuminate\Database\Eloquent\Collection;

function createCurrentMatch(): Game
{
    $game = Game::ensureSingleton(
        gameAttributes: [
            'number' => 1,
            'country_code' => 'ITA',
            'city' => 'Bologna',
            'hall' => 'PalaDozza',
            'date_time' => now()->addDay()->setTime(20, 30),
            'division' => 'Men',
            'pool' => 'A',
            'category' => 'Senior',
            'status' => MatchPhase::Setup,
        ],
        homeTeamAttributes: [
            'name' => 'Italy',
            'country_code' => 'ITA',
        ],
        awayTeamAttributes: [
            'name' => 'Brazil',
            'country_code' => 'BRA',
        ],
    );

    $game->resetForSetup();

    /** @var Game */
    return $game->fresh(['homeTeam', 'awayTeam', 'officials']);
}

function createCurrentMatchWithoutDetails(): Game
{
    $game = Game::ensureSingleton(
        gameAttributes: [
            'number' => 1,
            'country_code' => '',
            'city' => '',
            'hall' => '',
            'date_time' => now(),
            'division' => '',
            'pool' => '',
            'category' => '',
            'status' => MatchPhase::Setup,
        ],
        homeTeamAttributes: [
            'name' => '',
            'country_code' => '',
        ],
        awayTeamAttributes: [
            'name' => '',
            'country_code' => '',
        ],
    );

    $game->resetForSetup();

    /** @var Game */
    return $game->fresh(['homeTeam', 'awayTeam', 'officials']);
}

/**
 * @return array{
 *     home_players: Collection<int, Player>,
 *     away_players: Collection<int, Player>,
 *     home_staff: array<int, Staff>,
 *     away_staff: array<int, Staff>
 * }
 */
function seedRosterCandidates(Game $game, int $playersPerTeam = 7): array
{
    $homePlayers = Player::factory()->for($game->homeTeam)->count($playersPerTeam)->create();
    $awayPlayers = Player::factory()->for($game->awayTeam)->count($playersPerTeam)->create();

    $homeStaff = [
        Staff::factory()->for($game->homeTeam)->asCoach()->create(),
        Staff::factory()->for($game->homeTeam)->asDoctor()->create(),
    ];

    $awayStaff = [
        Staff::factory()->for($game->awayTeam)->asCoach()->create(),
        Staff::factory()->for($game->awayTeam)->asDoctor()->create(),
    ];

    return [
        'home_players' => $homePlayers,
        'away_players' => $awayPlayers,
        'home_staff' => $homeStaff,
        'away_staff' => $awayStaff,
    ];
}

function submitInitialRosters(Game $game, int $playersPerTeam = 6): Game
{
    $homePlayers = Player::factory()->for($game->homeTeam)->count($playersPerTeam)->create();
    $awayPlayers = Player::factory()->for($game->awayTeam)->count($playersPerTeam)->create();

    foreach ($homePlayers as $index => $player) {
        $game->addPlayer($player, number: $index + 1, isCaptain: $index === 0);
    }

    foreach ($awayPlayers as $index => $player) {
        $game->addPlayer($player, number: $index + 11, isCaptain: $index === 0);
    }

    $game->markRostersSubmitted();

    return $game->fresh();
}

function assignRequiredOfficials(Game $game): Game
{
    foreach (OfficialRole::cases() as $index => $role) {
        $game->addOfficial(
            Official::factory()
                ->named('Official', 'Crew'.($index + 1))
                ->withCountryCode($index % 2 === 0 ? 'ITA' : 'BRA')
                ->create(),
            $role,
        );
    }

    return $game->fresh();
}

function makeReadyCurrentMatch(): Game
{
    $game = createCurrentMatch();

    submitInitialRosters($game);
    assignRequiredOfficials($game);

    return $game->fresh();
}

/**
 * @return array<int, int>
 */
function standardLineup(int $startingNumber = 1): array
{
    return [
        1 => $startingNumber,
        2 => $startingNumber + 1,
        3 => $startingNumber + 2,
        4 => $startingNumber + 3,
        5 => $startingNumber + 4,
        6 => $startingNumber + 5,
    ];
}

function recordStraightSetsWin(Game $game, TeamAB $winner = TeamAB::TeamA, int $sets = 3): Game
{
    $game->recordToss(TeamSide::Home, TeamAB::TeamA);

    foreach (range(1, $sets) as $setNumber) {
        $game->recordLineup($setNumber, TeamAB::TeamA, standardLineup());
        $game->recordLineup($setNumber, TeamAB::TeamB, standardLineup(11));
        $game->recordSetStarted();

        foreach (range(1, 25) as $rally) {
            $game->recordRallyWinner($winner);
        }
    }

    return $game->fresh();
}
