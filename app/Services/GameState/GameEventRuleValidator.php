<?php

namespace App\Services\GameState;

use App\Enums\GameEventType;
use App\Exceptions\InvalidGameEventTransition;
use App\Models\Game;

class GameEventRuleValidator
{
    public function assertCanRecordToss(Game $game): void
    {
        $state = $game->stateAt();

        if ($state->gameEnded || $state->setNumber > 0 || $this->hasRecordedToss($game)) {
            $this->fail('The toss can only be recorded once before the first set starts.');
        }

    }

    public function assertCanRecordSetStarted(Game $game): void
    {
        $state = $game->stateAt();

        if (! $this->hasRecordedToss($game)) {
            $this->fail('A set cannot start before the toss has been recorded.');
        }

        if ($state->gameEnded || $state->setsWonTeamA >= 3 || $state->setsWonTeamB >= 3) {
            $this->fail('No additional sets can start after the game has been decided.');
        }

        if ($state->setInProgress) {
            $this->fail('A set is already in progress.');
        }
    }

    public function assertCanRecordLineup(Game $game, int $set): void
    {
        $state = $game->stateAt();

        if (! $this->hasRecordedToss($game)) {
            $this->fail('A lineup cannot be submitted before the toss has been recorded.');
        }

        if (! $state->setInProgress) {
            $this->fail('A lineup can only be submitted during an active set.');
        }

        if ($set !== $state->setNumber) {
            $this->fail('The lineup set number must match the current active set.');
        }
    }

    public function assertCanRecordRally(Game $game): void
    {
        $state = $game->stateAt();

        if ($state->gameEnded || ! $state->setInProgress) {
            $this->fail('A rally result can only be recorded while a set is in progress.');
        }
    }

    public function assertCanRecordSubstitution(Game $game): void
    {
        $state = $game->stateAt();

        if ($state->gameEnded || ! $state->setInProgress) {
            $this->fail('A substitution can only be recorded while a set is in progress.');
        }
    }

    public function assertCanRecordTimeOut(Game $game): void
    {
        $state = $game->stateAt();

        if ($state->gameEnded || ! $state->setInProgress) {
            $this->fail('A time-out can only be recorded while a set is in progress.');
        }
    }

    public function assertCanRecordSetEnded(Game $game): void
    {
        $state = $game->stateAt();

        if ($state->gameEnded || ! $state->setInProgress) {
            $this->fail('A set can only end while it is in progress.');
        }

        $scoreDiff = abs($state->scoreTeamA - $state->scoreTeamB);
        $highestScore = max($state->scoreTeamA, $state->scoreTeamB);
        if ($highestScore < 25 || $scoreDiff < 2) {
            $this->fail('A set can only end when a team has at least 25 points with a 2-point advantage.');
        }
    }

    public function assertCanRecordGameEnded(Game $game): void
    {
        $state = $game->stateAt();

        if ($state->gameEnded) {
            $this->fail('The game has already ended.');
        }

        if ($state->setInProgress) {
            $this->fail('The game cannot end while a set is still in progress.');
        }

        if ($state->setsWonTeamA < 3 && $state->setsWonTeamB < 3) {
            $this->fail('A game can only end after one team has won three sets.');
        }
    }

    private function hasRecordedToss(Game $game): bool
    {
        return $game->events()
            ->where('type', GameEventType::TossCompleted)
            ->exists();
    }

    private function fail(string $message): never
    {
        throw new InvalidGameEventTransition($message);
    }
}
