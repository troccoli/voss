<?php

declare(strict_types=1);

use App\Enums\StaffRole;
use App\Livewire\RosterSubmission;
use App\Models\Player;
use App\Models\Staff;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('game.between_sets_duration', 0);
});

test('initial roster submission is shown before the initial toss on the singleton match', function (): void {
    createCurrentMatch();

    Livewire::test(RosterSubmission::class)
        ->assertSee('Submit rosters')
        ->assertSeeHtml('data-submit-roster-button');
});

test('submit rosters modal shows the singleton match player pool and staff', function (): void {
    $game = createCurrentMatch();
    Player::factory()->for($game->homeTeam)->named('Anna', 'Zephyr')->create();
    Player::factory()->for($game->awayTeam)->named('Dora', 'Young')->create();
    Staff::factory()->for($game->homeTeam)->asCoach()->named('Helen', 'Coach')->create();
    Staff::factory()->for($game->awayTeam)->asDoctor()->named('Mila', 'Doctor')->create();

    Livewire::test(RosterSubmission::class)
        ->call('openRosterModal')
        ->assertSee('Anna Zephyr')
        ->assertSee('Dora Young')
        ->assertSee('Captain?')
        ->assertSee('Helen Coach')
        ->assertSee('Mila Doctor')
        ->assertSee(StaffRole::Coach->value)
        ->assertSee(StaffRole::Doctor->value);
});

test('roster submission records the selected roster on the singleton match', function (): void {
    $game = createCurrentMatch();
    $rosterCandidates = seedRosterCandidates($game);
    $homePlayers = $rosterCandidates['home_players'];
    $awayPlayers = $rosterCandidates['away_players'];
    $homeCoach = $rosterCandidates['home_staff'][0];
    $awayDoctor = $rosterCandidates['away_staff'][1];

    $component = Livewire::test(RosterSubmission::class);

    foreach ($homePlayers as $index => $player) {
        $component->set("homeRosterInputs.{$player->getKey()}", $index === 6 ? '12' : (string) ($index + 1));
    }

    foreach ($awayPlayers as $index => $player) {
        $component->set("awayRosterInputs.{$player->getKey()}", $index === 6 ? '22' : (string) ($index + 11));
    }

    $component
        ->set('homeCaptainSelection', (string) $homePlayers[0]->getKey())
        ->set('awayCaptainSelection', (string) $awayPlayers[0]->getKey())
        ->set("homeLiberoSelection.{$homePlayers[6]->getKey()}", true)
        ->set("awayLiberoSelection.{$awayPlayers[6]->getKey()}", true)
        ->set("homeStaffSelection.{$homeCoach->getKey()}", true)
        ->set("awayStaffSelection.{$awayDoctor->getKey()}", true)
        ->call('submitRosters')
        ->assertHasNoErrors();

    expect($game->fresh()->homePlayers)->toHaveCount(0)
        ->and($game->fresh()->awayPlayers)->toHaveCount(0)
        ->and($game->fresh()->rosters_submitted)->toBeFalse();

    $component
        ->assertSee('Confirm rosters')
        ->call('confirmRosters')
        ->assertHasNoErrors()
        ->assertDontSee('Submit rosters');

    $freshGame = $game->fresh();

    expect($freshGame->homePlayers)->toHaveCount(7)
        ->and($freshGame->awayPlayers)->toHaveCount(7)
        ->and($freshGame->homePlayers->firstWhere('id', $homePlayers[0]->getKey())?->is_captain)->toBeTrue()
        ->and($freshGame->homePlayers->firstWhere('id', $homePlayers[6]->getKey())?->number)->toBe(12)
        ->and($freshGame->homePlayers->firstWhere('id', $homePlayers[6]->getKey())?->is_libero)->toBeTrue()
        ->and($freshGame->awayPlayers->firstWhere('id', $awayPlayers[0]->getKey())?->is_captain)->toBeTrue()
        ->and($freshGame->awayPlayers->firstWhere('id', $awayPlayers[6]->getKey())?->number)->toBe(22)
        ->and($freshGame->awayPlayers->firstWhere('id', $awayPlayers[6]->getKey())?->is_libero)->toBeTrue()
        ->and($freshGame->homeStaff->first()?->getKey())->toBe($homeCoach->getKey())
        ->and($freshGame->awayStaff->first()?->getKey())->toBe($awayDoctor->getKey())
        ->and($freshGame->rosters_submitted)->toBeTrue();
});

test('submitting rosters requires six non-libero players per team', function (): void {
    $game = createCurrentMatch();
    $rosterCandidates = seedRosterCandidates($game, 6);
    $homePlayers = $rosterCandidates['home_players'];
    $awayPlayers = $rosterCandidates['away_players'];

    $component = Livewire::test(RosterSubmission::class);

    foreach ($homePlayers as $index => $player) {
        $component->set("homeRosterInputs.{$player->getKey()}", (string) ($index + 1));

        if ($index === 5) {
            $component->set("homeLiberoSelection.{$player->getKey()}", true);
        }
    }

    foreach ($awayPlayers as $index => $player) {
        $component->set("awayRosterInputs.{$player->getKey()}", (string) ($index + 11));
    }

    $component
        ->set('homeCaptainSelection', (string) $homePlayers[0]->getKey())
        ->set('awayCaptainSelection', (string) $awayPlayers[0]->getKey())
        ->call('submitRosters')
        ->assertHasErrors(['homeRosterInputs'])
        ->assertHasNoErrors(['awayRosterInputs']);
});
