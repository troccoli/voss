<?php

declare(strict_types=1);

use App\Enums\GameEventType;
use App\Enums\OfficialRole;
use App\Enums\StaffRole;
use App\Enums\TeamAB;
use App\Enums\TeamSide;
use App\Events\Payloads\TossCompletedPayload;
use App\Models\Game;
use App\Models\Official;
use App\Models\Player;
use App\Models\Staff;
use App\Models\Team;
use App\Services\ScoresheetDataRepository;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('match info uses configured competition metadata and single match setup data', function (): void {
    config()->set('competition.name', 'Nations League Finals');

    $game = configuredScoresheetGame();
    $repository = app(ScoresheetDataRepository::class);

    $matchInfo = $repository->matchInfo($game);

    expect($matchInfo['competition_name'])->toBe('Nations League Finals')
        ->and($matchInfo['city'])->toBe('Turin')
        ->and($matchInfo['country_code'])->toBe('ITA')
        ->and($matchInfo['hall'])->toBe('Pala Alpitour')
        ->and($matchInfo['pool'])->toBe('A')
        ->and($matchInfo['match_number'])->toBe(7)
        ->and($matchInfo['scheduled_at']->format('Y-m-d H:i'))->toBe('2026-06-09 18:30')
        ->and($matchInfo['division'])->toBe('Men')
        ->and($matchInfo['category'])->toBe('Senior')
        ->and($matchInfo['home_team_code'])->toBe('ITA')
        ->and($matchInfo['away_team_code'])->toBe('BRA');
});

test('players for side returns non-libero players for each side', function (): void {
    $game = gameWithNumberedRostersForScoresheetDataRepository();
    $repository = app(ScoresheetDataRepository::class);

    $homePlayers = $repository->playersForSide($game, TeamSide::Home);
    $awayPlayers = $repository->playersForSide($game, TeamSide::Away);

    expect(collect($homePlayers)->pluck('number')->all())->toBe([3, 12])
        ->and(collect($homePlayers)->pluck('last_name')->all())->toBe(['Anderson', 'Zephyr'])
        ->and(collect($awayPlayers)->pluck('number')->all())->toBe([2, 9])
        ->and(collect($awayPlayers)->pluck('last_name')->all())->toBe(['Baker', 'Young']);
});

test('players for side reflects roster changes on repeated reads', function (): void {
    $game = gameWithNumberedRostersForScoresheetDataRepository();
    $repository = app(ScoresheetDataRepository::class);

    $playersBeforeRosterChange = $repository->playersForSide($game, TeamSide::Home);

    $newHomePlayer = Player::factory()->for($game->homeTeam)->named('Nina', 'Newest')->create();
    $game->addPlayer($newHomePlayer, number: 8);

    $playersAfterRosterChange = $repository->playersForSide($game, TeamSide::Home);

    expect(collect($playersBeforeRosterChange)->pluck('player_key')->all())->not->toContain($newHomePlayer->getKey())
        ->and(collect($playersAfterRosterChange)->pluck('player_key')->all())->toContain($newHomePlayer->getKey());
});

test('team sheet groups players liberos and staff from the singleton match roster', function (): void {
    $game = configuredScoresheetGame();
    $repository = app(ScoresheetDataRepository::class);

    $homeTeamSheet = $repository->teamSheet($game, TeamSide::Home);

    expect($homeTeamSheet['team_code'])->toBe('ITA')
        ->and(collect($homeTeamSheet['players'])->pluck('number')->all())->toBe([3, 12])
        ->and(collect($homeTeamSheet['players'])->pluck('is_captain')->all())->toBe([false, true])
        ->and(collect($homeTeamSheet['liberos'])->pluck('number')->all())->toBe([1])
        ->and(collect($homeTeamSheet['staff'])->pluck('role')->all())->toBe([StaffRole::Coach, StaffRole::AssistantCoach]);
});

test('official assignments are returned in scoresheet order', function (): void {
    $game = configuredScoresheetGame();
    $repository = app(ScoresheetDataRepository::class);

    $officials = $repository->officials($game);

    expect(collect($officials)->pluck('role')->all())->toBe(OfficialRole::cases())
        ->and($officials[0]['country_code'])->toBe('ITA')
        ->and($officials[1]['country_code'])->toBe('BRA');
});

test('latest toss payload returns the recorded toss payload', function (): void {
    $game = gameWithNumberedRostersForScoresheetDataRepository();
    $game->recordToss(TeamSide::Away, TeamAB::TeamB);

    $repository = app(ScoresheetDataRepository::class);
    $payload = $repository->latestTossPayload($game);

    expect($payload)->not->toBeNull()
        ->and($payload?->teamA)->toBe(TeamSide::Away)
        ->and($payload?->serving)->toBe(TeamAB::TeamB);
});

test('latest toss payload reflects new toss events on repeated reads', function (): void {
    $game = gameWithNumberedRostersForScoresheetDataRepository();
    $game->recordToss(TeamSide::Home, TeamAB::TeamA);

    $repository = app(ScoresheetDataRepository::class);

    $payloadBeforeSecondToss = $repository->latestTossPayload($game);
    $game->events()->create([
        'type' => GameEventType::TossCompleted,
        'payload' => new TossCompletedPayload(
            teamA: TeamSide::Away,
            serving: TeamAB::TeamB,
        ),
    ]);

    $payloadAfterRecordingToss = $repository->latestTossPayload($game);

    expect($payloadBeforeSecondToss)->not->toBeNull()
        ->and($payloadBeforeSecondToss?->teamA)->toBe(TeamSide::Home)
        ->and($payloadBeforeSecondToss?->serving)->toBe(TeamAB::TeamA)
        ->and($payloadAfterRecordingToss?->teamA)->toBe(TeamSide::Away)
        ->and($payloadAfterRecordingToss?->serving)->toBe(TeamAB::TeamB);
});

test('results are derived from the recorded event log', function (): void {
    $game = configuredScoresheetGame(withBenchPlayers: true);
    recordCompletedMatch($game);

    $repository = app(ScoresheetDataRepository::class);
    $results = $repository->results($game);

    expect($results['team_a_code'])->toBe('ITA')
        ->and($results['team_b_code'])->toBe('BRA')
        ->and($results['winner_team_code'])->toBe('ITA')
        ->and($results['team_a_sets_won'])->toBe(3)
        ->and($results['team_b_sets_won'])->toBe(1)
        ->and($results['match_start_time']?->format('H:i'))->toBe('18:00')
        ->and($results['match_end_time']?->format('H:i'))->toBe('19:58')
        ->and($results['total_duration_minutes'])->toBe(118)
        ->and($results['total_set_duration_minutes'])->toBe(92)
        ->and($results['sets'])->toHaveCount(4)
        ->and($results['sets'][0])->toMatchArray([
            'set_number' => 1,
            'team_a_points' => 25,
            'team_b_points' => 18,
            'team_a_timeouts' => 1,
            'team_b_timeouts' => 0,
            'team_a_substitutions' => 1,
            'team_b_substitutions' => 0,
            'team_a_sets_won' => 1,
            'team_b_sets_won' => 0,
            'duration_minutes' => 20,
        ])
        ->and($results['sets'][1])->toMatchArray([
            'set_number' => 2,
            'team_a_points' => 20,
            'team_b_points' => 25,
            'team_a_sets_won' => 1,
            'team_b_sets_won' => 1,
        ])
        ->and($results['sets'][3])->toMatchArray([
            'set_number' => 4,
            'team_a_points' => 25,
            'team_b_points' => 21,
            'team_a_sets_won' => 3,
            'team_b_sets_won' => 1,
            'duration_minutes' => 24,
        ]);
});

function gameWithNumberedRostersForScoresheetDataRepository(): Game
{
    $homeTeam = Team::factory()->create();
    $awayTeam = Team::factory()->create();
    $game = Game::factory()->betweenTeams($homeTeam, $awayTeam)->create();

    $homePlayerOne = Player::factory()->for($homeTeam)->named('Anna', 'Zephyr')->create();
    $homePlayerTwo = Player::factory()->for($homeTeam)->named('Beth', 'Anderson')->create();
    $homeLibero = Player::factory()->for($homeTeam)->named('Cara', 'Libero')->create();

    $awayPlayerOne = Player::factory()->for($awayTeam)->named('Dora', 'Young')->create();
    $awayPlayerTwo = Player::factory()->for($awayTeam)->named('Etta', 'Baker')->create();
    $awayLibero = Player::factory()->for($awayTeam)->named('Faye', 'Keeper')->create();

    $game->addPlayer($homePlayerOne, number: 12);
    $game->addPlayer($homePlayerTwo, number: 3);
    $game->addPlayer($homeLibero, number: 1, isLibero: true);
    $game->addPlayer($awayPlayerOne, number: 9);
    $game->addPlayer($awayPlayerTwo, number: 2);
    $game->addPlayer($awayLibero, number: 20, isLibero: true);
    $game->markRostersSubmitted();

    foreach (OfficialRole::cases() as $index => $role) {
        $game->addOfficial(
            Official::factory()
                ->named('Official', 'Numbered'.($index + 1))
                ->withCountryCode($index % 2 === 0 ? 'ITA' : 'BRA')
                ->create(),
            $role,
        );
    }

    return $game->fresh();
}

function configuredScoresheetGame(bool $withBenchPlayers = false): Game
{
    $homeTeam = Team::factory()->named('Italy')->withCountryCode('ITA')->create();
    $awayTeam = Team::factory()->named('Brazil')->withCountryCode('BRA')->create();
    $game = Game::factory()->betweenTeams($homeTeam, $awayTeam)->create();
    $game->forceFill([
        'number' => 7,
        'country_code' => 'ITA',
        'city' => 'Turin',
        'hall' => 'Pala Alpitour',
        'date_time' => CarbonImmutable::parse('2026-06-09 18:30:00'),
        'division' => 'Men',
        'pool' => 'A',
        'category' => 'Senior',
    ])->save();

    $homePlayerOne = Player::factory()->for($homeTeam)->named('Anna', 'Zephyr')->create();
    $homePlayerTwo = Player::factory()->for($homeTeam)->named('Beth', 'Anderson')->create();
    $homeLibero = Player::factory()->for($homeTeam)->named('Cara', 'Libero')->create();
    $awayPlayerOne = Player::factory()->for($awayTeam)->named('Dora', 'Young')->create();
    $awayPlayerTwo = Player::factory()->for($awayTeam)->named('Etta', 'Baker')->create();
    $awayLibero = Player::factory()->for($awayTeam)->named('Faye', 'Keeper')->create();

    $game->addPlayer($homePlayerOne, number: 12, isCaptain: true);
    $game->addPlayer($homePlayerTwo, number: 3);
    $game->addPlayer($homeLibero, number: 1, isLibero: true);
    $game->addPlayer($awayPlayerOne, number: 9, isCaptain: true);
    $game->addPlayer($awayPlayerTwo, number: 2);
    $game->addPlayer($awayLibero, number: 20, isLibero: true);

    if ($withBenchPlayers) {
        foreach (range(4, 7) as $number) {
            $game->addPlayer(
                Player::factory()->for($homeTeam)->named('Home', 'Bench'.$number)->create(),
                number: $number,
            );
        }

        foreach (range(11, 17) as $number) {
            $game->addPlayer(
                Player::factory()->for($awayTeam)->named('Away', 'Bench'.$number)->create(),
                number: $number,
            );
        }
    }

    $game->addStaff(Staff::factory()->for($homeTeam)->asCoach()->named('Hugo', 'Coach')->create());
    $game->addStaff(Staff::factory()->for($homeTeam)->asAssistantCoach()->named('Ivy', 'Assistant')->create());
    $game->addStaff(Staff::factory()->for($awayTeam)->asCoach()->named('Joao', 'Coach')->create());
    $game->addStaff(Staff::factory()->for($awayTeam)->asDoctor()->named('Lia', 'Doctor')->create());
    $game->markRostersSubmitted();

    foreach (OfficialRole::cases() as $index => $role) {
        $countryCode = $index % 2 === 0 ? 'ITA' : 'BRA';
        $game->addOfficial(
            Official::factory()
                ->named('Official', 'Role'.($index + 1))
                ->withCountryCode($countryCode)
                ->create(),
            $role,
        );
    }

    return $game->fresh();
}

function recordCompletedMatch(Game $game): void
{
    CarbonImmutable::setTestNow('2026-06-09 17:55:00');
    $game->recordToss(TeamSide::Home, TeamAB::TeamA);

    recordCompletedSet(
        game: $game,
        setNumber: 1,
        startedAt: '2026-06-09 18:00:00',
        endedAt: '2026-06-09 18:20:00',
        winner: TeamAB::TeamA,
        winnerPoints: 25,
        loserPoints: 18,
        teamATimeouts: 1,
        teamBTimeouts: 0,
        teamASubstitutions: [[12, 4]],
        teamBSubstitutions: [],
    );

    recordCompletedSet(
        game: $game,
        setNumber: 2,
        startedAt: '2026-06-09 18:28:00',
        endedAt: '2026-06-09 18:50:00',
        winner: TeamAB::TeamB,
        winnerPoints: 25,
        loserPoints: 20,
        teamATimeouts: 0,
        teamBTimeouts: 1,
        teamASubstitutions: [],
        teamBSubstitutions: [[11, 17]],
    );

    recordCompletedSet(
        game: $game,
        setNumber: 3,
        startedAt: '2026-06-09 19:00:00',
        endedAt: '2026-06-09 19:26:00',
        winner: TeamAB::TeamA,
        winnerPoints: 25,
        loserPoints: 23,
        teamATimeouts: 1,
        teamBTimeouts: 1,
        teamASubstitutions: [],
        teamBSubstitutions: [],
    );

    recordCompletedSet(
        game: $game,
        setNumber: 4,
        startedAt: '2026-06-09 19:34:00',
        endedAt: '2026-06-09 19:58:00',
        winner: TeamAB::TeamA,
        winnerPoints: 25,
        loserPoints: 21,
        teamATimeouts: 0,
        teamBTimeouts: 2,
        teamASubstitutions: [],
        teamBSubstitutions: [],
    );

    CarbonImmutable::setTestNow();
}

/**
 * @param  array<int, array{0: int, 1: int}>  $teamASubstitutions
 * @param  array<int, array{0: int, 1: int}>  $teamBSubstitutions
 */
function recordCompletedSet(
    Game $game,
    int $setNumber,
    string $startedAt,
    string $endedAt,
    TeamAB $winner,
    int $winnerPoints,
    int $loserPoints,
    int $teamATimeouts,
    int $teamBTimeouts,
    array $teamASubstitutions,
    array $teamBSubstitutions,
): void {
    CarbonImmutable::setTestNow($startedAt);
    $game->recordLineup($setNumber, TeamAB::TeamA, [1 => 12, 2 => 3, 3 => 4, 4 => 5, 5 => 6, 6 => 7]);
    $game->recordLineup($setNumber, TeamAB::TeamB, [1 => 9, 2 => 2, 3 => 11, 4 => 12, 5 => 13, 6 => 14]);
    $game->recordSetStarted();

    if ($teamATimeouts > 0) {
        foreach (range(1, $teamATimeouts) as $timeout) {
            $game->recordTimeOut(TeamAB::TeamA);
        }
    }

    if ($teamBTimeouts > 0) {
        foreach (range(1, $teamBTimeouts) as $timeout) {
            $game->recordTimeOut(TeamAB::TeamB);
        }
    }

    foreach ($teamASubstitutions as [$playerOut, $playerIn]) {
        $game->recordSubstitution(TeamAB::TeamA, $playerOut, $playerIn);
    }

    foreach ($teamBSubstitutions as [$playerOut, $playerIn]) {
        $game->recordSubstitution(TeamAB::TeamB, $playerOut, $playerIn);
    }

    $loser = $winner === TeamAB::TeamA ? TeamAB::TeamB : TeamAB::TeamA;

    if ($loserPoints > 0) {
        foreach (range(1, $loserPoints) as $rally) {
            $game->recordRallyWinner($loser);
        }
    }

    CarbonImmutable::setTestNow($endedAt);

    if ($winnerPoints > 0) {
        foreach (range(1, $winnerPoints) as $rally) {
            $game->recordRallyWinner($winner);
        }
    }
}
