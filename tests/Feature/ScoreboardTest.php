<?php

declare(strict_types=1);

use App\Data\GameState\GameState;
use App\Enums\GameEventType;
use App\Enums\TeamAB;
use App\Enums\TeamSide;
use App\Events\Payloads\SetStartedPayload;
use App\Livewire\Scoreboard;
use App\Models\Game;
use App\Models\GameEvent;
use App\Models\GameStateSnapshot;
use App\Models\Team;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

test('scoreboard shows team country codes based on team a and team b toss assignment', function (): void {
    $game = gameWithDistinctTeamCountryCodes();
    $game->recordToss(TeamSide::Away, TeamAB::TeamA);

    $teamACode = $game->awayTeam->country_code;
    $teamBCode = $game->homeTeam->country_code;

    Livewire::test(Scoreboard::class, [
        'gameId' => $game->getKey(),
        'gameState' => GameState::fromAttributes([
            'sets_won_team_a' => 0,
            'sets_won_team_b' => 0,
            'score_team_a' => 14,
            'score_team_b' => 19,
        ]),
    ])
        ->assertSeeHtml('data-scoreboard-left-team="team_a"')
        ->assertSeeHtml('data-scoreboard-right-team="team_b"')
        ->assertSeeInOrder([
            $teamACode,
            'Sets',
            $teamBCode,
            '0',
            ':',
            '0',
            $teamACode,
            'Points',
            $teamBCode,
            '14',
            ':',
            '19',
        ]);
});

test('scoreboard swaps left and right teams when completed set count is odd', function (): void {
    $game = gameWithDistinctTeamCountryCodes();
    $game->recordToss(TeamSide::Home, TeamAB::TeamA);

    $teamACode = $game->homeTeam->country_code;
    $teamBCode = $game->awayTeam->country_code;

    Livewire::test(Scoreboard::class, [
        'gameId' => $game->getKey(),
        'gameState' => GameState::fromAttributes([
            'sets_won_team_a' => 2,
            'sets_won_team_b' => 1,
            'score_team_a' => 17,
            'score_team_b' => 21,
        ]),
    ])
        ->assertSeeHtml('data-scoreboard-left-team="team_b"')
        ->assertSeeHtml('data-scoreboard-right-team="team_a"')
        ->assertSeeInOrder([
            $teamBCode,
            'Sets',
            $teamACode,
            '1',
            ':',
            '2',
            $teamBCode,
            'Points',
            $teamACode,
            '21',
            ':',
            '17',
        ]);
});

test('scoreboard resolves team a side from latest snapshot without requiring toss event lookup', function (): void {
    $game = gameWithDistinctTeamCountryCodes();

    $stateEvent = GameEvent::withoutEvents(fn (): GameEvent => GameEvent::query()->create([
        'game_id' => $game->getKey(),
        'type' => GameEventType::SetStarted,
        'payload' => new SetStartedPayload,
        'created_at' => Carbon::now(),
    ]));

    GameStateSnapshot::query()->create([
        'game_id' => $game->getKey(),
        'game_event_id' => $stateEvent->getKey(),
        'set_number' => 1,
        'score_team_a' => 0,
        'score_team_b' => 0,
        'sets_won_team_a' => 0,
        'sets_won_team_b' => 0,
        'timeouts_team_a' => 0,
        'timeouts_team_b' => 0,
        'substitutions_team_a' => 0,
        'substitutions_team_b' => 0,
        'team_a_side' => TeamSide::Away->value,
        'serving_team' => TeamAB::TeamA->value,
        'rotation_team_a' => [],
        'rotation_team_b' => [],
        'set_in_progress' => false,
        'game_ended' => false,
        'created_at' => Carbon::now(),
    ]);

    $teamACode = $game->awayTeam->country_code;
    $teamBCode = $game->homeTeam->country_code;

    Livewire::test(Scoreboard::class, [
        'gameId' => $game->getKey(),
        'gameState' => GameState::fromAttributes([
            'sets_won_team_a' => 0,
            'sets_won_team_b' => 0,
            'score_team_a' => 8,
            'score_team_b' => 4,
        ]),
    ])
        ->assertSeeHtml('data-scoreboard-left-team="team_a"')
        ->assertSeeHtml('data-scoreboard-right-team="team_b"')
        ->assertSeeInOrder([
            $teamACode,
            'Sets',
            $teamBCode,
            '0',
            ':',
            '0',
            $teamACode,
            'Points',
            $teamBCode,
            '8',
            ':',
            '4',
        ]);
});

function gameWithDistinctTeamCountryCodes(): Game
{
    $homeTeam = Team::factory()->create();
    $awayTeam = Team::factory()->create();

    while ($awayTeam->country_code === $homeTeam->country_code) {
        $awayTeam = Team::factory()->create();
    }

    return Game::factory()->betweenTeams($homeTeam, $awayTeam)->create();
}
