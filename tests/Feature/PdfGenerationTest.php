<?php

declare(strict_types=1);

use App\Enums\MatchPhase;
use App\Exceptions\InvalidGameEventTransition;
use App\Models\Game;
use App\Services\Scoresheet\ScoresheetGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('game.between_sets_duration', 0);
    fakeScoresheetPdfFactory();
});

test('pdf generation command writes the singleton match scoresheet after completion', function (): void {
    $game = makeReadyCurrentMatch();
    recordStraightSetsWin($game);

    $outputPath = storage_path('app/public/scoresheet.pdf');

    if (file_exists($outputPath)) {
        unlink($outputPath);
    }

    $this->artisan('app:generate-pdf')
        ->assertSuccessful();

    expect(file_exists($outputPath))->toBeTrue()
        ->and(Game::current()->fresh()->status)->toBe(MatchPhase::PdfGenerated);
});

test('pdf generation is blocked until the singleton match is completed', function (): void {
    $game = makeReadyCurrentMatch();

    expect(fn () => app(ScoresheetGenerator::class)->generate($game))
        ->toThrow(InvalidGameEventTransition::class);
});
