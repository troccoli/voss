<?php

declare(strict_types=1);

use App\Enums\MatchPhase;
use App\Enums\TeamAB;
use App\Enums\TeamSide;
use App\Exceptions\InvalidGameEventTransition;
use App\Livewire\MatchSetup;
use App\Services\Scoresheet\ScoresheetGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('game.between_sets_duration', 0);
    fakeScoresheetPdfFactory();
});

test('match status progresses from setup to ready to in progress to completed and pdf generated', function (): void {
    $game = makeReadyCurrentMatch();

    expect($game->fresh()->status)->toBe(MatchPhase::Ready);

    $game->recordToss(TeamSide::Home, TeamAB::TeamA);

    expect($game->fresh()->status)->toBe(MatchPhase::InProgress);

    foreach (range(1, 3) as $setNumber) {
        $game->recordLineup($setNumber, TeamAB::TeamA, standardLineup());
        $game->recordLineup($setNumber, TeamAB::TeamB, standardLineup(11));
        $game->recordSetStarted();

        foreach (range(1, 25) as $rally) {
            $game->recordRallyWinner(TeamAB::TeamA);
        }
    }

    expect($game->fresh()->status)->toBe(MatchPhase::Completed);

    app(ScoresheetGenerator::class)->generate($game->fresh());

    expect($game->fresh()->status)->toBe(MatchPhase::PdfGenerated);

    app(ScoresheetGenerator::class)->generate($game->fresh());

    expect($game->fresh()->status)->toBe(MatchPhase::PdfGenerated);
});

test('setup edits are blocked after gameplay begins in the domain', function (): void {
    $game = makeReadyCurrentMatch();
    $game->recordToss(TeamSide::Home, TeamAB::TeamA);

    expect(fn () => $game->fresh()->replaceOfficials([]))
        ->toThrow(InvalidGameEventTransition::class, 'Match setup cannot be edited after gameplay has begun.');
});

test('match setup ui rejects edits after gameplay begins', function (): void {
    makeReadyCurrentMatch()->recordToss(TeamSide::Home, TeamAB::TeamA);

    Livewire::test(MatchSetup::class)
        ->call('saveOfficials')
        ->assertHasErrors(['setup']);
});
