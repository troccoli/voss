<?php

declare(strict_types=1);

use App\Enums\OfficialRole;
use App\Enums\TeamAB;
use App\Enums\TeamSide;
use App\Models\Game;
use App\Models\Official;
use App\Models\Player;
use App\Models\Staff;
use App\Models\Team;
use App\Services\Scoresheet\Writers\MatchInfoWriter;
use App\Services\Scoresheet\Writers\ResultsWriter;
use App\Services\Scoresheet\Writers\TeamsWriter;
use Carbon\CarbonImmutable;

test('match info writer renders configured setup values', function (): void {
    config()->set('competition.name', 'Nations League Finals');

    $game = configuredScoresheetWriterGame();
    $pdf = new RecordingScoresheetPdf;

    app(MatchInfoWriter::class)->write($pdf, $game);

    expect(collect($pdf->writes)->pluck('text')->all())->toContain('Nations League Finals')
        ->and(collect($pdf->spacedPrints)->pluck('text')->all())->toContain(
            'Turin',
            'ITA',
            'Pala Alpitour',
            'A',
            '7',
            '090626',
            '1830',
            'BRA',
        )
        ->and($pdf->lines)->toHaveCount(4);
});

test('teams writer renders rosters captains liberos and staff', function (): void {
    $game = configuredScoresheetWriterGame();
    $pdf = new RecordingScoresheetPdf;

    app(TeamsWriter::class)->write($pdf, $game);

    expect(collect($pdf->spacedPrints)->pluck('text')->all())->toContain('ITA', 'BRA')
        ->and(collect($pdf->writes)->pluck('text')->all())->toContain(
            '12',
            '3',
            '1',
            'ZEPHYR',
            'ANDERSON',
            'LIBERO',
            'COACH, H.',
            'ASSISTANT, I.',
        )
        ->and($pdf->circles)->toHaveCount(2);
});

test('results writer renders final score from the recorded event log', function (): void {
    $game = configuredScoresheetWriterGame(withBenchPlayers: true);
    recordCompletedScoresheetWriterMatch($game);
    $pdf = new RecordingScoresheetPdf;

    app(ResultsWriter::class)->write($pdf, $game->fresh());

    expect(collect($pdf->spacedPrints)->pluck('text')->all())->toContain('ITA', 'BRA', '1800', '1958')
        ->and(collect($pdf->writes)->pluck('text')->all())->toContain('25', '18', '20', '22', '26', '24', '92', '3 : 1')
        ->and(last($pdf->spacedPrints)['text'])->toBe('ITA');
});
function configuredScoresheetWriterGame(bool $withBenchPlayers = false): Game
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

function recordCompletedScoresheetWriterMatch(Game $game): void
{
    CarbonImmutable::setTestNow('2026-06-09 17:55:00');
    $game->recordToss(TeamSide::Home, TeamAB::TeamA);

    recordCompletedScoresheetWriterSet(
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

    recordCompletedScoresheetWriterSet(
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

    recordCompletedScoresheetWriterSet(
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

    recordCompletedScoresheetWriterSet(
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
function recordCompletedScoresheetWriterSet(
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
