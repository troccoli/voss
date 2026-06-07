<?php

declare(strict_types=1);

namespace App\Services\GameState;

use App\Data\GameState\GameState;
use App\Enums\GameEventType;
use App\Enums\MisconductSanction;
use App\Enums\MisconductSubjectType;
use App\Enums\TeamAB;
use App\Events\Payloads\MisconductRecordedPayload;
use App\Exceptions\InvalidGameEventTransition;
use App\Models\Game;
use App\Models\GameEvent;
use Carbon\CarbonImmutable;

class GameEventRuleValidator
{
    public function __construct(
        protected SetScoringRules $setScoringRules
    ) {}

    public function assertCanRecordToss(Game $game): void
    {
        $state = $game->stateAt();

        if (! $this->hasRecordedToss($game)) {
            if ($state->gameEnded || $state->setNumber > 0) {
                $this->fail('The toss can only be recorded before the first set starts or before the fifth set.');
            }

            return;
        }

        if (! $this->requiresFifthSetToss($state) || $state->fifthSetLeftTeam !== null) {
            $this->fail('The toss can only be recorded before the first set starts or before the fifth set.');
        }
    }

    public function assertCanRecordSetStarted(Game $game): void
    {
        $state = $game->stateAt();

        if (! $this->hasRequiredToss($state)) {
            $this->fail('A set cannot start before the toss has been recorded.');
        }

        if ($state->gameEnded || $state->setsWonTeamA >= 3 || $state->setsWonTeamB >= 3) {
            $this->fail('No additional sets can start after the game has been decided.');
        }

        if ($state->setInProgress) {
            $this->fail('A set is already in progress.');
        }

        if ($this->setBreakIsInProgress($game, $state)) {
            $this->fail('The interval between sets has not elapsed yet.');
        }

        $upcomingSet = $state->setNumber + 1;
        $hasTeamALineup = $this->hasSubmittedLineupForSet($game, TeamAB::TeamA, $upcomingSet);
        $hasTeamBLineup = $this->hasSubmittedLineupForSet($game, TeamAB::TeamB, $upcomingSet);

        if (! $hasTeamALineup || ! $hasTeamBLineup) {
            $this->fail('Both team lineups must be submitted before starting the set.');
        }
    }

    public function assertCanRecordLineup(Game $game, int $set): void
    {
        $state = $game->stateAt();

        if (! $this->hasRequiredToss($state)) {
            $this->fail('A lineup cannot be submitted before the toss has been recorded.');
        }

        if ($state->gameEnded || $state->setsWonTeamA >= 3 || $state->setsWonTeamB >= 3) {
            $this->fail('A lineup cannot be submitted after the game has ended.');
        }

        if ($state->setInProgress) {
            $this->fail('A lineup can only be submitted before the set starts.');
        }

        $expectedSet = $state->setNumber + 1;

        if ($set !== $expectedSet) {
            $this->fail('The lineup set number must match the upcoming set.');
        }
    }

    public function assertCanRecordRally(Game $game): void
    {
        $state = $game->stateAt();

        if ($state->gameEnded || ! $state->setInProgress) {
            $this->fail('A rally result can only be recorded while a set is in progress.');
        }
    }

    public function assertCanRecordCourtSidesSwapped(Game $game): void
    {
        $state = $game->stateAt();

        if ($state->gameEnded || ! $state->setInProgress || $state->setNumber !== 5) {
            $this->fail('Court sides can only be swapped during the fifth set.');
        }

        if ($state->fifthSetLeftTeam === null) {
            $this->fail('Court sides cannot be swapped before the fifth set toss has been recorded.');
        }

        if ($state->fifthSetSideSwapped) {
            $this->fail('Court sides have already been swapped in the fifth set.');
        }

        if (max($state->scoreTeamA, $state->scoreTeamB) < 8) {
            $this->fail('Court sides can only be swapped once a team reaches 8 points in the fifth set.');
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

    public function assertCanRecordImproperRequest(Game $game): void
    {
        $state = $game->stateAt();

        if ($state->gameEnded || ! $state->setInProgress) {
            $this->fail('An improper request can only be recorded while a set is in progress.');
        }
    }

    public function assertCanRecordDelayWarning(Game $game, TeamAB $team): void
    {
        $state = $game->stateAt();

        if ($state->gameEnded) {
            $this->fail('A delay warning cannot be recorded after the game has ended.');
        }

        $hasDelaySanction = $team === TeamAB::TeamA
            ? $state->delayWarningsTeamA + $state->delayPenaltiesTeamA > 0
            : $state->delayWarningsTeamB + $state->delayPenaltiesTeamB > 0;

        if ($hasDelaySanction) {
            $this->fail('This team already has a delay warning.');
        }
    }

    public function assertCanRecordDelayPenalty(Game $game): void
    {
        $state = $game->stateAt();

        if ($state->gameEnded) {
            $this->fail('A delay penalty cannot be recorded after the game has ended.');
        }
    }

    public function assertCanRecordMisconduct(
        Game $game,
        TeamAB $team,
        MisconductSubjectType $subjectType,
        int $subjectId,
        MisconductSanction $sanction,
    ): void {
        $state = $game->stateAt();

        if ($state->gameEnded) {
            $this->fail('Misconduct cannot be recorded after the game has ended.');
        }

        $hasMinorMisconduct = $team === TeamAB::TeamA
            ? $state->misconductWarningsTeamA > 0
            : $state->misconductWarningsTeamB > 0;

        if ($sanction === MisconductSanction::Warning && $hasMinorMisconduct) {
            $this->fail('This team already has a minor misconduct warning.');
        }

        $teamId = $team === TeamAB::TeamA ? $game->home_team_id : $game->away_team_id;

        $isRostered = match ($subjectType) {
            MisconductSubjectType::Player => $game->players()
                ->whereKey($subjectId)
                ->wherePivot('team_id', $teamId)
                ->exists(),
            MisconductSubjectType::Staff => $game->staff()
                ->whereKey($subjectId)
                ->wherePivot('team_id', $teamId)
                ->exists(),
        };

        if (! $isRostered) {
            $this->fail('Misconduct can only be recorded for a rostered player or staff member.');
        }

        $highestRecordedSanction = $game->events()
            ->where('type', GameEventType::MisconductRecorded)
            ->get()
            ->map(fn (GameEvent $event): mixed => $event->payload)
            ->filter(fn (mixed $payload): bool => $payload instanceof MisconductRecordedPayload
                && $payload->team === $team
                && $payload->subjectType === $subjectType
                && $payload->subjectId === $subjectId)
            ->map(fn (MisconductRecordedPayload $payload): int => $this->misconductSanctionRank($payload->sanction))
            ->max();

        if ($highestRecordedSanction !== null && $this->misconductSanctionRank($sanction) <= $highestRecordedSanction) {
            $this->fail('This person already has the same or a higher misconduct sanction.');
        }
    }

    public function assertCanRecordSetEnded(Game $game): void
    {
        $state = $game->stateAt();

        if ($state->gameEnded || ! $state->setInProgress) {
            $this->fail('A set can only end while it is in progress.');
        }

        $targetPoints = $this->setScoringRules->targetPoints($state->setNumber);

        if (! $this->setScoringRules->canEndSet($state->setNumber, $state->scoreTeamA, $state->scoreTeamB)) {
            $this->fail("A set can only end when a team has at least {$targetPoints} points with a 2-point advantage.");
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

    private function hasRequiredToss(GameState $state): bool
    {
        if ($state->teamASide === null || $state->servingTeam === null) {
            return false;
        }

        if (! $this->requiresFifthSetToss($state)) {
            return true;
        }

        return $state->fifthSetLeftTeam !== null;
    }

    private function requiresFifthSetToss(GameState $state): bool
    {
        return ! $state->gameEnded
            && ! $state->setInProgress
            && $state->setNumber === 4
            && $state->setsWonTeamA === 2
            && $state->setsWonTeamB === 2;
    }

    private function hasSubmittedLineupForSet(Game $game, TeamAB $team, int $setNumber): bool
    {
        return $game->events()
            ->where('type', GameEventType::LineupSubmitted)
            ->where('payload->set', $setNumber)
            ->where('payload->team', $team->value)
            ->exists();
    }

    private function setBreakIsInProgress(Game $game, GameState $state): bool
    {
        if ($state->setNumber === 0 || $state->gameEnded) {
            return false;
        }

        $lastSetEndedAt = $this->lastSetEndedAt($game);

        if ($lastSetEndedAt === null) {
            return false;
        }

        return $lastSetEndedAt
            ->addSeconds($this->betweenSetsDuration())
            ->isFuture();
    }

    private function lastSetEndedAt(Game $game): ?CarbonImmutable
    {
        /** @var GameEvent|null $setEndedEvent */
        $setEndedEvent = $game->events()
            ->reorder()
            ->where('type', GameEventType::SetEnded)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first();

        return $setEndedEvent?->created_at;
    }

    private function betweenSetsDuration(): int
    {
        return max(0, (int) config('game.between_sets_duration', 180));
    }

    private function fail(string $message): never
    {
        throw new InvalidGameEventTransition($message);
    }

    private function misconductSanctionRank(MisconductSanction $sanction): int
    {
        return match ($sanction) {
            MisconductSanction::Warning => 1,
            MisconductSanction::Penalty => 2,
            MisconductSanction::Expulsion => 3,
            MisconductSanction::Disqualification => 4,
        };
    }
}
