<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\MatchPhase;
use App\Models\Game;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\MultipleRecordsFoundException;
use LogicException;

class CurrentMatchResolver
{
    public function current(): ?Game
    {
        try {
            /** @var Game */
            return Game::query()->sole();
        } catch (ModelNotFoundException) {
            return null;
        } catch (MultipleRecordsFoundException $exception) {
            throw new LogicException('The single-match application found multiple game records.', previous: $exception);
        }
    }

    public function currentOrFail(): Game
    {
        $game = $this->current();

        if ($game !== null) {
            return $game;
        }

        $exception = new ModelNotFoundException;
        $exception->setModel(Game::class);

        throw $exception;
    }

    public function landingRouteName(): string
    {
        $currentGame = $this->current();

        if ($currentGame === null) {
            return 'match.setup';
        }

        return $currentGame->status === MatchPhase::Setup
            ? 'match.setup'
            : 'game';
    }

    public function nextStep(?Game $game = null): string
    {
        $currentGame = $game ?? $this->current();

        if ($currentGame === null) {
            return 'missing-match';
        }

        if ($currentGame->status !== MatchPhase::Setup) {
            return 'ready';
        }

        if (! $currentGame->hasCompleteMatchDetails()) {
            return 'match-details';
        }

        if (! $currentGame->hasSubmittedInitialRosters()) {
            return 'rosters';
        }

        if (! $currentGame->hasRequiredOfficials()) {
            return 'officials';
        }

        return 'ready';
    }

    public function isSetupComplete(?Game $game = null): bool
    {
        $currentGame = $game ?? $this->current();

        return $currentGame !== null && $currentGame->status !== MatchPhase::Setup;
    }
}
