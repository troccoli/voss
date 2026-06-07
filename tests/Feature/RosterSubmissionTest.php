<?php

declare(strict_types=1);

use App\Enums\StaffRole;
use App\Livewire\RosterSubmission;
use App\Models\Game;
use App\Models\Player;
use App\Models\Staff;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('game.between_sets_duration', 0);
});

test('initial roster submission is shown before the initial toss', function (): void {
    $game = Game::factory()->create();

    Livewire::test(RosterSubmission::class, ['gameId' => $game->getKey()])
        ->assertSee('Submit rosters')
        ->assertSeeHtml('data-submit-roster-button');
});

test('submit rosters modal shows home and away players and staff', function (): void {
    $game = Game::factory()->create();
    Player::factory()->for($game->homeTeam)->named('Anna', 'Zephyr')->create();
    Player::factory()->for($game->awayTeam)->named('Dora', 'Young')->create();
    Staff::factory()->for($game->homeTeam)->asCoach()->named('Helen', 'Coach')->create();
    Staff::factory()->for($game->awayTeam)->asDoctor()->named('Mila', 'Doctor')->create();

    Livewire::test(RosterSubmission::class, ['gameId' => $game->getKey()])
        ->call('openRosterModal')
        ->assertSee('Anna Zephyr')
        ->assertSee('Dora Young')
        ->assertSee('Captain?')
        ->assertSee('Helen Coach')
        ->assertSee('Mila Doctor')
        ->assertSee(StaffRole::Coach->value)
        ->assertSee(StaffRole::Doctor->value);
});

test('submitting rosters opens confirmation and confirm saves selected players and checked staff for both teams', function (): void {
    $game = Game::factory()->create();
    $homePlayers = Player::factory()->for($game->homeTeam)->count(7)->create();
    $awayPlayers = Player::factory()->for($game->awayTeam)->count(7)->create();
    $homeCoach = Staff::factory()->for($game->homeTeam)->asCoach()->create();
    $awayDoctor = Staff::factory()->for($game->awayTeam)->asDoctor()->create();

    $component = Livewire::test(RosterSubmission::class, ['gameId' => $game->getKey()]);

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
        ->assertSee($homePlayers[0]->last_name.' '.mb_substr((string) $homePlayers[0]->first_name, 0, 1).'.')
        ->assertSee((string) 12)
        ->assertSee('Bench')
        ->call('confirmRosters')
        ->assertHasNoErrors()
        ->assertDontSee('Submit rosters');

    $freshGame = $game->fresh();

    expect($freshGame->homePlayers)->toHaveCount(7)
        ->and($freshGame->awayPlayers)->toHaveCount(7)
        ->and($freshGame->homePlayers->firstWhere('id', $homePlayers[0]->getKey())?->roster->is_captain)->toBeTrue()
        ->and($freshGame->homePlayers->firstWhere('id', $homePlayers[6]->getKey())?->roster->number)->toBe(12)
        ->and($freshGame->homePlayers->firstWhere('id', $homePlayers[6]->getKey())?->roster->is_libero)->toBeTrue()
        ->and($freshGame->awayPlayers->firstWhere('id', $awayPlayers[0]->getKey())?->roster->is_captain)->toBeTrue()
        ->and($freshGame->awayPlayers->firstWhere('id', $awayPlayers[6]->getKey())?->roster->number)->toBe(22)
        ->and($freshGame->awayPlayers->firstWhere('id', $awayPlayers[6]->getKey())?->roster->is_libero)->toBeTrue()
        ->and($freshGame->homeStaff->first()?->getKey())->toBe($homeCoach->getKey())
        ->and($freshGame->awayStaff->first()?->getKey())->toBe($awayDoctor->getKey())
        ->and($freshGame->rosters_submitted)->toBeTrue();
});

test('confirming rosters only saves the checked staff members for each team', function (): void {
    $game = Game::factory()->create();
    $homePlayers = Player::factory()->for($game->homeTeam)->count(6)->create();
    $awayPlayers = Player::factory()->for($game->awayTeam)->count(6)->create();
    $homeCoach = Staff::factory()->for($game->homeTeam)->asCoach()->create();
    $homeDoctor = Staff::factory()->for($game->homeTeam)->asDoctor()->create();
    $homeTherapist = Staff::factory()->for($game->homeTeam)->asTherapist()->create();
    $awayCoach = Staff::factory()->for($game->awayTeam)->asCoach()->create();
    $awayAssistantCoach = Staff::factory()->for($game->awayTeam)->asAssistantCoach()->create();
    $awayDoctor = Staff::factory()->for($game->awayTeam)->asDoctor()->create();

    $component = Livewire::test(RosterSubmission::class, ['gameId' => $game->getKey()]);

    foreach ($homePlayers as $index => $player) {
        $component->set("homeRosterInputs.{$player->getKey()}", (string) ($index + 1));
    }

    foreach ($awayPlayers as $index => $player) {
        $component->set("awayRosterInputs.{$player->getKey()}", (string) ($index + 11));
    }

    $component
        ->set('homeCaptainSelection', (string) $homePlayers[0]->getKey())
        ->set('awayCaptainSelection', (string) $awayPlayers[0]->getKey())
        ->set("homeStaffSelection.{$homeCoach->getKey()}", true)
        ->set("homeStaffSelection.{$homeDoctor->getKey()}", true)
        ->set("awayStaffSelection.{$awayAssistantCoach->getKey()}", true)
        ->call('confirmRosters')
        ->assertHasNoErrors();

    $freshGame = $game->fresh();

    expect($freshGame->homeStaff->modelKeys())
        ->toBe([$homeCoach->getKey(), $homeDoctor->getKey()])
        ->and($freshGame->homeStaff->contains(fn (Staff $staff): bool => $staff->getKey() === $homeTherapist->getKey()))->toBeFalse()
        ->and($freshGame->awayStaff->modelKeys())->toBe([$awayAssistantCoach->getKey()])
        ->and($freshGame->awayStaff->contains(fn (Staff $staff): bool => $staff->getKey() === $awayCoach->getKey()))->toBeFalse()
        ->and($freshGame->awayStaff->contains(fn (Staff $staff): bool => $staff->getKey() === $awayDoctor->getKey()))->toBeFalse();
});

test('returning to roster from confirmation preserves entered selections', function (): void {
    $game = Game::factory()->create();
    $homePlayers = Player::factory()->for($game->homeTeam)->count(6)->create();
    $awayPlayers = Player::factory()->for($game->awayTeam)->count(6)->create();

    $component = Livewire::test(RosterSubmission::class, ['gameId' => $game->getKey()]);

    foreach ($homePlayers as $index => $player) {
        $component->set("homeRosterInputs.{$player->getKey()}", (string) ($index + 1));
    }

    foreach ($awayPlayers as $index => $player) {
        $component->set("awayRosterInputs.{$player->getKey()}", (string) ($index + 11));
    }

    $component
        ->set('homeCaptainSelection', (string) $homePlayers[1]->getKey())
        ->set('awayCaptainSelection', (string) $awayPlayers[2]->getKey())
        ->set("homeLiberoSelection.{$homePlayers[5]->getKey()}", true)
        ->set("awayLiberoSelection.{$awayPlayers[4]->getKey()}", true)
        ->call('submitRosters')
        ->assertSee('Confirm rosters')
        ->call('returnToRoster')
        ->assertSet("homeRosterInputs.{$homePlayers[0]->getKey()}", '1')
        ->assertSet("awayRosterInputs.{$awayPlayers[0]->getKey()}", '11')
        ->assertSet('homeCaptainSelection', (string) $homePlayers[1]->getKey())
        ->assertSet('awayCaptainSelection', (string) $awayPlayers[2]->getKey())
        ->assertSet("homeLiberoSelection.{$homePlayers[5]->getKey()}", true)
        ->assertSet("awayLiberoSelection.{$awayPlayers[4]->getKey()}", true);
});

test('confirmation groups and orders roster details for each team', function (): void {
    $game = Game::factory()->create();

    $homePlayers = collect([
        Player::factory()->for($game->homeTeam)->named('Alice', 'Zulu')->create(),
        Player::factory()->for($game->homeTeam)->named('Beth', 'Able')->create(),
        Player::factory()->for($game->homeTeam)->named('Cara', 'Mason')->create(),
        Player::factory()->for($game->homeTeam)->named('Dana', 'Nolan')->create(),
        Player::factory()->for($game->homeTeam)->named('Eve', 'Olsen')->create(),
        Player::factory()->for($game->homeTeam)->named('Faye', 'Piper')->create(),
        Player::factory()->for($game->homeTeam)->named('Gia', 'Quill')->create(),
    ]);

    $awayPlayers = Player::factory()->for($game->awayTeam)->count(6)->create();

    $homeCoach = Staff::factory()->for($game->homeTeam)->asCoach()->named('Holly', 'Coach')->create();
    $assistantBravo = Staff::factory()->for($game->homeTeam)->asAssistantCoach()->named('Iris', 'Bravo')->create();
    $assistantAlpha = Staff::factory()->for($game->homeTeam)->asAssistantCoach()->named('Jade', 'Alpha')->create();
    $therapist = Staff::factory()->for($game->homeTeam)->asTherapist()->named('Kora', 'Therapist')->create();
    $doctor = Staff::factory()->for($game->homeTeam)->asDoctor()->named('Lina', 'Doctor')->create();

    $component = Livewire::test(RosterSubmission::class, ['gameId' => $game->getKey()]);

    foreach ($homePlayers as $index => $player) {
        $number = match ($index) {
            0 => '8',
            1 => '3',
            2 => '11',
            3 => '5',
            4 => '7',
            5 => '2',
            default => '13',
        };

        $component->set("homeRosterInputs.{$player->getKey()}", $number);
    }

    foreach ($awayPlayers as $index => $player) {
        $component->set("awayRosterInputs.{$player->getKey()}", (string) ($index + 21));
    }

    $component
        ->set('homeCaptainSelection', (string) $homePlayers[1]->getKey())
        ->set('awayCaptainSelection', (string) $awayPlayers[0]->getKey())
        ->set("homeLiberoSelection.{$homePlayers[6]->getKey()}", true)
        ->set("homeStaffSelection.{$homeCoach->getKey()}", true)
        ->set("homeStaffSelection.{$assistantBravo->getKey()}", true)
        ->set("homeStaffSelection.{$assistantAlpha->getKey()}", true)
        ->set("homeStaffSelection.{$therapist->getKey()}", true)
        ->set("homeStaffSelection.{$doctor->getKey()}", true)
        ->call('submitRosters')
        ->assertSeeInOrder([
            'Players',
            '3',
            'Able B.',
            '5',
            'Nolan D.',
            '7',
            'Olsen E.',
            '8',
            'Zulu A.',
            '11',
            'Mason C.',
            'Liberos',
            '13',
            'Quill G.',
            'Bench',
            'C',
            'Coach H.',
            'AC1',
            'Alpha J.',
            'AC2',
            'Bravo I.',
            'T',
            'Therapist K.',
            'D',
            'Doctor L.',
        ]);
});

test('submitting rosters requires six non-libero players per team', function (): void {
    $game = Game::factory()->create();
    $homePlayers = Player::factory()->for($game->homeTeam)->count(6)->create();
    $awayPlayers = Player::factory()->for($game->awayTeam)->count(6)->create();

    $component = Livewire::test(RosterSubmission::class, ['gameId' => $game->getKey()]);

    foreach ($homePlayers as $index => $player) {
        $component->set("homeRosterInputs.{$player->getKey()}", $index === 5 ? '7' : (string) ($index + 1));
    }

    foreach ($awayPlayers as $index => $player) {
        $component->set("awayRosterInputs.{$player->getKey()}", (string) ($index + 11));
    }

    $component
        ->set('homeCaptainSelection', (string) $homePlayers[0]->getKey())
        ->set('awayCaptainSelection', (string) $awayPlayers[0]->getKey())
        ->set("homeLiberoSelection.{$homePlayers[5]->getKey()}", true)
        ->call('confirmRosters')
        ->assertHasErrors(['homeRosterInputs']);
});

test('submitting rosters requires player numbers to be between 1 and 99', function (): void {
    $game = Game::factory()->create();
    $homePlayers = Player::factory()->for($game->homeTeam)->count(6)->create();
    $awayPlayers = Player::factory()->for($game->awayTeam)->count(6)->create();

    $component = Livewire::test(RosterSubmission::class, ['gameId' => $game->getKey()]);

    foreach ($homePlayers as $index => $player) {
        $component->set("homeRosterInputs.{$player->getKey()}", $index === 0 ? '0' : (string) ($index + 1));
    }

    foreach ($awayPlayers as $index => $player) {
        $component->set("awayRosterInputs.{$player->getKey()}", $index === 0 ? '100' : (string) ($index + 11));
    }

    $component
        ->set('homeCaptainSelection', (string) $homePlayers[0]->getKey())
        ->set('awayCaptainSelection', (string) $awayPlayers[0]->getKey())
        ->call('confirmRosters')
        ->assertHasErrors([
            "homeRosterInputs.{$homePlayers[0]->getKey()}",
            "awayRosterInputs.{$awayPlayers[0]->getKey()}",
        ]);
});

test('submitting rosters requires unique player numbers within each team including liberos', function (): void {
    $game = Game::factory()->create();
    $homePlayers = Player::factory()->for($game->homeTeam)->count(7)->create();
    $awayPlayers = Player::factory()->for($game->awayTeam)->count(6)->create();

    $component = Livewire::test(RosterSubmission::class, ['gameId' => $game->getKey()]);

    foreach ($homePlayers as $index => $player) {
        $component->set("homeRosterInputs.{$player->getKey()}", $index === 6 ? '1' : (string) ($index + 1));
    }

    foreach ($awayPlayers as $index => $player) {
        $component->set("awayRosterInputs.{$player->getKey()}", (string) ($index + 11));
    }

    $component
        ->set('homeCaptainSelection', (string) $homePlayers[0]->getKey())
        ->set('awayCaptainSelection', (string) $awayPlayers[0]->getKey())
        ->set("homeLiberoSelection.{$homePlayers[6]->getKey()}", true)
        ->call('confirmRosters')
        ->assertHasErrors(["homeRosterInputs.{$homePlayers[6]->getKey()}"]);
});

test('submitting rosters allows at most two liberos per team', function (): void {
    $game = Game::factory()->create();
    $homePlayers = Player::factory()->for($game->homeTeam)->count(8)->create();
    $awayPlayers = Player::factory()->for($game->awayTeam)->count(6)->create();

    $component = Livewire::test(RosterSubmission::class, ['gameId' => $game->getKey()]);

    foreach ($homePlayers as $index => $player) {
        $component->set("homeRosterInputs.{$player->getKey()}", (string) ($index + 1));
    }

    foreach ($awayPlayers as $index => $player) {
        $component->set("awayRosterInputs.{$player->getKey()}", (string) ($index + 11));
    }

    $component
        ->set('homeCaptainSelection', (string) $homePlayers[0]->getKey())
        ->set('awayCaptainSelection', (string) $awayPlayers[0]->getKey())
        ->set("homeLiberoSelection.{$homePlayers[5]->getKey()}", true)
        ->set("homeLiberoSelection.{$homePlayers[6]->getKey()}", true)
        ->set("homeLiberoSelection.{$homePlayers[7]->getKey()}", true)
        ->call('confirmRosters')
        ->assertHasErrors(['homeRosterInputs']);
});

test('submitting rosters allows at most twelve non-libero players per team', function (): void {
    $game = Game::factory()->create();
    $homePlayers = Player::factory()->for($game->homeTeam)->count(14)->create();
    $awayPlayers = Player::factory()->for($game->awayTeam)->count(6)->create();

    $component = Livewire::test(RosterSubmission::class, ['gameId' => $game->getKey()]);

    foreach ($homePlayers as $index => $player) {
        $component->set("homeRosterInputs.{$player->getKey()}", (string) ($index + 1));
    }

    foreach ($awayPlayers as $index => $player) {
        $component->set("awayRosterInputs.{$player->getKey()}", (string) ($index + 21));
    }

    $component
        ->set('homeCaptainSelection', (string) $homePlayers[0]->getKey())
        ->set('awayCaptainSelection', (string) $awayPlayers[0]->getKey())
        ->set("homeLiberoSelection.{$homePlayers[12]->getKey()}", true)
        ->set("homeLiberoSelection.{$homePlayers[13]->getKey()}", true)
        ->call('confirmRosters')
        ->assertHasNoErrors(['homeRosterInputs']);

    $component
        ->set("homeLiberoSelection.{$homePlayers[12]->getKey()}", false)
        ->call('confirmRosters')
        ->assertHasErrors(['homeRosterInputs']);
});

test('submitting rosters requires a libero to be a rostered player', function (): void {
    $game = Game::factory()->create();
    $homePlayers = Player::factory()->for($game->homeTeam)->count(6)->create();
    $awayPlayers = Player::factory()->for($game->awayTeam)->count(6)->create();

    $component = Livewire::test(RosterSubmission::class, ['gameId' => $game->getKey()]);

    foreach ($homePlayers as $index => $player) {
        $component->set("homeRosterInputs.{$player->getKey()}", $index === 5 ? '' : (string) ($index + 1));
    }

    foreach ($awayPlayers as $index => $player) {
        $component->set("awayRosterInputs.{$player->getKey()}", (string) ($index + 11));
    }

    $component
        ->set('homeCaptainSelection', (string) $homePlayers[0]->getKey())
        ->set('awayCaptainSelection', (string) $awayPlayers[0]->getKey())
        ->set("homeLiberoSelection.{$homePlayers[5]->getKey()}", true)
        ->call('confirmRosters')
        ->assertHasErrors(["homeRosterInputs.{$homePlayers[5]->getKey()}"])
        ->assertHasNoErrors(['homeCaptainSelection']);
});

test('submitting rosters requires one captain per team', function (): void {
    $game = Game::factory()->create();
    $homePlayers = Player::factory()->for($game->homeTeam)->count(6)->create();
    $awayPlayers = Player::factory()->for($game->awayTeam)->count(6)->create();

    $component = Livewire::test(RosterSubmission::class, ['gameId' => $game->getKey()]);

    foreach ($homePlayers as $index => $player) {
        $component->set("homeRosterInputs.{$player->getKey()}", (string) ($index + 1));
    }

    foreach ($awayPlayers as $index => $player) {
        $component->set("awayRosterInputs.{$player->getKey()}", (string) ($index + 11));
    }

    $component
        ->set('awayCaptainSelection', (string) $awayPlayers[0]->getKey())
        ->call('confirmRosters')
        ->assertHasErrors(['homeCaptainSelection']);
});

test('submitting rosters requires the captain to be one of the rostered players', function (): void {
    $game = Game::factory()->create();
    $homePlayers = Player::factory()->for($game->homeTeam)->count(6)->create();
    $awayPlayers = Player::factory()->for($game->awayTeam)->count(6)->create();

    $component = Livewire::test(RosterSubmission::class, ['gameId' => $game->getKey()]);

    foreach ($homePlayers as $index => $player) {
        $component->set("homeRosterInputs.{$player->getKey()}", (string) ($index + 1));
    }

    foreach ($awayPlayers as $index => $player) {
        $component->set("awayRosterInputs.{$player->getKey()}", (string) ($index + 11));
    }

    $component
        ->set('homeCaptainSelection', (string) $homePlayers[0]->getKey())
        ->set('awayCaptainSelection', (string) $awayPlayers[5]->getKey())
        ->set("awayRosterInputs.{$awayPlayers[5]->getKey()}", '')
        ->call('confirmRosters')
        ->assertHasErrors(['awayCaptainSelection'])
        ->assertHasNoErrors(["awayRosterInputs.{$awayPlayers[5]->getKey()}"]);
});

test('roster validation errors clear when the user fixes the input', function (): void {
    $game = Game::factory()->create();
    $homePlayers = Player::factory()->for($game->homeTeam)->count(6)->create();
    $awayPlayers = Player::factory()->for($game->awayTeam)->count(6)->create();

    $component = Livewire::test(RosterSubmission::class, ['gameId' => $game->getKey()]);

    foreach ($homePlayers as $index => $player) {
        $component->set("homeRosterInputs.{$player->getKey()}", $index === 1 ? '1' : (string) ($index + 1));
    }

    foreach ($awayPlayers as $index => $player) {
        $component->set("awayRosterInputs.{$player->getKey()}", (string) ($index + 11));
    }

    $component
        ->set('homeCaptainSelection', (string) $homePlayers[0]->getKey())
        ->set('awayCaptainSelection', (string) $awayPlayers[0]->getKey())
        ->call('confirmRosters')
        ->assertHasErrors(["homeRosterInputs.{$homePlayers[1]->getKey()}"]);

    $component
        ->set("homeRosterInputs.{$homePlayers[1]->getKey()}", '2')
        ->assertHasNoErrors(["homeRosterInputs.{$homePlayers[1]->getKey()}"]);
});

function seedRosterableTeams(Game $game): void
{
    $homePlayers = Player::factory()->for($game->homeTeam)->count(6)->create();
    $awayPlayers = Player::factory()->for($game->awayTeam)->count(6)->create();

    $component = Livewire::test(RosterSubmission::class, ['gameId' => $game->getKey()]);

    foreach ($homePlayers as $index => $player) {
        $component->set("homeRosterInputs.{$player->getKey()}", (string) ($index + 1));
    }

    foreach ($awayPlayers as $index => $player) {
        $component->set("awayRosterInputs.{$player->getKey()}", (string) ($index + 11));
    }

    $component
        ->set('homeCaptainSelection', (string) $homePlayers[0]->getKey())
        ->set('awayCaptainSelection', (string) $awayPlayers[0]->getKey())
        ->call('confirmRosters')
        ->assertHasNoErrors();
}
