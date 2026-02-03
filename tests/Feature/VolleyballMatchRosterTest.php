<?php

use App\Enums\StaffRole;
use App\Models\MatchPlayer;
use App\Models\MatchStaff;
use App\Models\Player;
use App\Models\Staff;
use App\Models\Team;
use App\Models\VolleyballMatch;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a match has match players', function () {
    $match = VolleyballMatch::factory()->create();
    $team = Team::factory()->create();
    $player = Player::factory()->for($team)->create(['number' => 10]);

    $match->addPlayer($player);

    expect($match->matchPlayers)->toHaveCount(1)
        ->and($match->matchPlayers->first()->player->id)->toBe($player->id)
        ->and($match->matchPlayers->first()->number)->toBe(10);
});

test('can override player number in match', function () {
    $match = VolleyballMatch::factory()->create();
    $team = Team::factory()->create();
    $player = Player::factory()->for($team)->create(['number' => 10]);

    $match->addPlayer($player, number: 99);

    expect($match->matchPlayers->first()->number)->toBe(99);
});

test('a match has match staff', function () {
    $match = VolleyballMatch::factory()->create();
    $team = Team::factory()->create();
    $staff = Staff::factory()->for($team)->create();

    MatchStaff::factory()->create([
        'volleyball_match_id' => $match->id,
        'staff_id' => $staff->id,
        'team_id' => $team->id,
        'role' => StaffRole::Coach,
    ]);

    expect($match->matchStaff)->toHaveCount(1)
        ->and($match->matchStaff->first()->staff->id)->toBe($staff->id)
        ->and($match->matchStaff->first()->role)->toBe(StaffRole::Coach);
});

test('can access home and away rosters separately', function () {
    $homeTeam = Team::factory()->create();
    $awayTeam = Team::factory()->create();
    $match = VolleyballMatch::factory()->betweenTeams($homeTeam, $awayTeam)->create();

    $homePlayer = Player::factory()->for($homeTeam)->create();
    $awayPlayer = Player::factory()->for($awayTeam)->create();

    MatchPlayer::factory()->create([
        'volleyball_match_id' => $match->id,
        'player_id' => $homePlayer->id,
        'team_id' => $homeTeam->id,
    ]);

    MatchPlayer::factory()->create([
        'volleyball_match_id' => $match->id,
        'player_id' => $awayPlayer->id,
        'team_id' => $awayTeam->id,
    ]);

    expect($match->homePlayers)->toHaveCount(1)
        ->and($match->awayPlayers)->toHaveCount(1)
        ->and($match->homePlayers->first()->player->id)->toBe($homePlayer->id)
        ->and($match->awayPlayers->first()->player->id)->toBe($awayPlayer->id);
});

test('players are not captains or liberos by default in the player model', function () {
    $player = Player::factory()->create();

    // In Eloquent, accessing a missing attribute returns null
    expect($player->is_captain)->toBeNull()
        ->and($player->is_libero)->toBeNull();
});

test('can set captain and libero in match roster', function () {
    $match = VolleyballMatch::factory()->create();
    $team = Team::factory()->create();
    $player1 = Player::factory()->for($team)->create();
    $player2 = Player::factory()->for($team)->create();

    $matchPlayer1 = MatchPlayer::factory()->create([
        'volleyball_match_id' => $match->id,
        'player_id' => $player1->id,
        'team_id' => $team->id,
        'is_captain' => true,
    ]);

    $matchPlayer2 = MatchPlayer::factory()->create([
        'volleyball_match_id' => $match->id,
        'player_id' => $player2->id,
        'team_id' => $team->id,
        'is_libero' => true,
    ]);

    expect($matchPlayer1->is_captain)->toBeTrue()
        ->and($matchPlayer1->is_libero)->toBeFalse()
        ->and($matchPlayer2->is_libero)->toBeTrue()
        ->and($matchPlayer2->is_captain)->toBeFalse();
});
