<?php

declare(strict_types=1);

use App\Livewire\Competition as CompetitionComponent;
use App\Models\Competition;
use App\Models\Game;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('competition page is available', function (): void {
    $this->get(route('competition'))
        ->assertSuccessful()
        ->assertSee('Set the competition details');
});

test('competition page seeds the singleton from config when missing', function (): void {
    config()->set('competition.name', 'World Cup Finals');

    Livewire::test(CompetitionComponent::class)
        ->assertSet('name', 'World Cup Finals')
        ->call('save')
        ->assertSet('saved', true);

    expect(Competition::query()->count())->toBe(1)
        ->and(Competition::query()->sole()->name)->toBe('World Cup Finals');
});

test('competition page updates the current competition details', function (): void {
    $competition = Competition::factory()
        ->named('Nations League Finals')
        ->create();

    Livewire::test(CompetitionComponent::class)
        ->assertSet('name', $competition->name)
        ->set('name', 'European Competition')
        ->call('save')
        ->assertSet('saved', true);

    expect(Competition::query()->count())->toBe(1)
        ->and(Competition::query()->sole()->name)->toBe('European Competition');
});

test('game competition name prefers the stored competition', function (): void {
    config()->set('competition.name', 'Fallback Competition');

    Competition::factory()
        ->named('Stored Competition')
        ->create();

    $game = Game::factory()->create();

    expect($game->competitionName())->toBe('Stored Competition');
});
