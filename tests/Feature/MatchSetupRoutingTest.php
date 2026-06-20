<?php

declare(strict_types=1);

use App\Enums\OfficialRole;
use App\Livewire\MatchSetup;
use App\Models\Game;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('game.between_sets_duration', 0);
});

test('first load with no match configured redirects to setup and shows the singleton prompt', function (): void {
    $this->get(route('home'))
        ->assertRedirect(route('match.setup'));

    $this->get(route('game'))
        ->assertRedirect(route('match.setup'));

    $this->get(route('match.setup'))
        ->assertSuccessful()
        ->assertSee('Create the current match')
        ->assertSee('Create current match');
});

test('game redirects to setup when current match setup is incomplete', function (): void {
    createCurrentMatchWithoutDetails();

    $this->get(route('game'))
        ->assertRedirect(route('match.setup'));
});

test('setup page shows the next required step for an incomplete current match', function (): void {
    createCurrentMatchWithoutDetails();

    $this->get(route('match.setup'))
        ->assertSuccessful()
        ->assertSee('Match details')
        ->assertDontSee('Open current match');
});

test('home redirects to the match when setup is ready', function (): void {
    makeReadyCurrentMatch();

    $this->get(route('home'))
        ->assertRedirect(route('game'));
});

test('setup can create the current match when none exists', function (): void {
    Livewire::test(MatchSetup::class)
        ->call('createMatch')
        ->assertSet('step', 'match-details')
        ->assertSee('Match details');

    expect(Game::query()->count())->toBe(1);
});

test('setup reuses the current match instead of inserting another one', function (): void {
    $game = createCurrentMatchWithoutDetails();

    Livewire::test(MatchSetup::class)
        ->call('createMatch')
        ->assertSet('step', 'match-details');

    expect(Game::query()->count())->toBe(1)
        ->and(Game::query()->sole()->getKey())->toBe($game->getKey());
});

test('setup createMatch reuses a singleton created after the component mounts', function (): void {
    $component = Livewire::test(MatchSetup::class);

    $game = createCurrentMatchWithoutDetails();

    $component
        ->call('createMatch')
        ->assertSet('step', 'match-details');

    expect(Game::query()->count())->toBe(1)
        ->and(Game::query()->sole()->getKey())->toBe($game->getKey());
});

test('setup flow can take a blank current match to a playable ready state', function (): void {
    createCurrentMatchWithoutDetails();

    $component = Livewire::test(MatchSetup::class)
        ->set('matchNumber', '7')
        ->set('matchCountryCode', 'ITA')
        ->set('city', 'Rome')
        ->set('hall', 'Forum')
        ->set('matchDateTime', '2026-06-07T19:30')
        ->set('division', 'Men')
        ->set('pool', 'A')
        ->set('category', 'Senior')
        ->set('homeTeamName', 'Italy')
        ->set('homeTeamCountryCode', 'ITA')
        ->set('awayTeamName', 'Brazil')
        ->set('awayTeamCountryCode', 'BRA')
        ->call('saveMatchDetails')
        ->assertSet('step', 'rosters');

    foreach (range(0, 5) as $index) {
        $component
            ->set("homePlayerRows.{$index}.first_name", 'Home'.$index)
            ->set("homePlayerRows.{$index}.last_name", 'Player'.$index)
            ->set("homePlayerRows.{$index}.number", (string) ($index + 1))
            ->set("awayPlayerRows.{$index}.first_name", 'Away'.$index)
            ->set("awayPlayerRows.{$index}.last_name", 'Player'.$index)
            ->set("awayPlayerRows.{$index}.number", (string) ($index + 11));
    }

    $component
        ->set('homeCaptainSelection', '0')
        ->set('awayCaptainSelection', '0')
        ->call('saveRosters')
        ->assertSet('step', 'officials');

    foreach (OfficialRole::cases() as $index => $role) {
        $component
            ->set("officialRows.{$index}.first_name", 'Official'.$index)
            ->set("officialRows.{$index}.last_name", 'Crew'.$index)
            ->set("officialRows.{$index}.country_code", 'ITA');
    }

    $component
        ->call('saveOfficials')
        ->assertSet('step', 'ready')
        ->assertSee('Match ready');

    $this->get(route('game'))
        ->assertSuccessful();
});
