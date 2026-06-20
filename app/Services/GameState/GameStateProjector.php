<?php

declare(strict_types=1);

namespace App\Services\GameState;

use App\Data\GameState\GameState;
use App\Enums\GameEventType;
use App\Enums\MisconductSanction;
use App\Enums\TeamAB;
use App\Events\Payloads\CourtSidesSwappedPayload;
use App\Events\Payloads\DelayPenaltyRecordedPayload;
use App\Events\Payloads\DelayWarningRecordedPayload;
use App\Events\Payloads\ImproperRequestRecordedPayload;
use App\Events\Payloads\LineupSubmittedPayload;
use App\Events\Payloads\MisconductRecordedPayload;
use App\Events\Payloads\RallyEndedPayload;
use App\Events\Payloads\SubstitutionCompletedPayload;
use App\Events\Payloads\TimeOutRequestedPayload;
use App\Events\Payloads\TossCompletedPayload;
use App\Models\Game;
use App\Models\GameEvent;
use App\Models\GameStateSnapshot;
use Illuminate\Database\Eloquent\Builder;

class GameStateProjector
{
    private bool $resolvedTossServingTeam = false;

    private ?TeamAB $tossServingTeam = null;

    public function project(GameState $state, GameEvent $event): GameState
    {
        return match ($event->type) {
            GameEventType::TossCompleted => $this->applyTossCompleted($state, $event),
            GameEventType::LineupSubmitted => $this->applyLineupSubmitted($state, $event),
            GameEventType::RallyEnded => $this->applyRallyEnded($state, $event),
            GameEventType::CourtSidesSwapped => $this->applyCourtSidesSwapped($state, $event),
            GameEventType::SubstitutionCompleted => $this->applySubstitutionCompleted($state, $event),
            GameEventType::TimeOutRequested => $this->applyTimeOutRequested($state, $event),
            GameEventType::ImproperRequestRecorded => $this->applyImproperRequestRecorded($state, $event),
            GameEventType::DelayWarningRecorded => $this->applyDelayWarningRecorded($state, $event),
            GameEventType::DelayPenaltyRecorded => $this->applyDelayPenaltyRecorded($state, $event),
            GameEventType::MisconductRecorded => $this->applyMisconductRecorded($state, $event),
            GameEventType::SetStarted => $this->applySetStarted($state, $event),
            GameEventType::SetEnded => $this->applySetEnded($state, $event),
            GameEventType::GameEnded => $this->applyGameEnded($state),
        };
    }

    public function projectAndStore(GameEvent $event): GameStateSnapshot
    {
        $previousSnapshot = GameStateSnapshot::query()
            ->where('game_id', $event->game_id)
            ->where(function (Builder $query) use ($event): void {
                $query
                    ->where('created_at', '<', $event->created_at)
                    ->orWhere(function (Builder $nestedQuery) use ($event): void {
                        $nestedQuery
                            ->where('created_at', $event->created_at)
                            ->where('game_event_id', '<', $event->getKey());
                    });
            })
            ->orderByDesc('created_at')
            ->orderByDesc('game_event_id')
            ->first();

        $state = $previousSnapshot === null
            ? GameState::initial()
            : GameState::fromSnapshot($previousSnapshot);

        $state = $this->project($state, $event);

        return GameStateSnapshot::query()->create([
            'game_id' => $event->game_id,
            'game_event_id' => $event->getKey(),
            ...$state->toAttributes(),
            'created_at' => $event->created_at,
        ]);
    }

    private function applyTossCompleted(GameState $state, GameEvent $event): GameState
    {
        /** @var TossCompletedPayload $payload */
        $payload = $event->payload;
        $state->teamASide = $payload->teamA;
        $state->servingTeam = $payload->serving;

        if ($this->requiresFifthSetToss($state)) {
            $state->fifthSetLeftTeam = $payload->leftTeam;
            $state->fifthSetSideSwapped = false;
        }

        return $state;
    }

    private function applyCourtSidesSwapped(GameState $state, GameEvent $event): GameState
    {
        /** @var CourtSidesSwappedPayload $payload */
        $payload = $event->payload;

        if ($state->fifthSetLeftTeam !== null) {
            $state->fifthSetLeftTeam = $this->oppositeTeam($state->fifthSetLeftTeam);
        }

        $state->fifthSetSideSwapped = true;

        return $state;
    }

    private function applyLineupSubmitted(GameState $state, GameEvent $event): GameState
    {
        /** @var LineupSubmittedPayload $payload */
        $payload = $event->payload;

        if ($payload->team === TeamAB::TeamA) {
            $state->rotationTeamA = $payload->positions;
        } else {
            $state->rotationTeamB = $payload->positions;
        }

        return $state;
    }

    private function applyRallyEnded(GameState $state, GameEvent $event): GameState
    {
        /** @var RallyEndedPayload $payload */
        $payload = $event->payload;

        $this->awardRallyTo($state, $payload->team);

        return $state;
    }

    private function applySubstitutionCompleted(GameState $state, GameEvent $event): GameState
    {
        /** @var SubstitutionCompletedPayload $payload */
        $payload = $event->payload;

        if ($payload->team === TeamAB::TeamA) {
            $state->substitutionsTeamA++;
            $state->rotationTeamA = $this->substitute($state->rotationTeamA, $payload->playerOut, $payload->playerIn);
        } else {
            $state->substitutionsTeamB++;
            $state->rotationTeamB = $this->substitute($state->rotationTeamB, $payload->playerOut, $payload->playerIn);
        }

        return $state;
    }

    private function applyDelayPenaltyRecorded(GameState $state, GameEvent $event): GameState
    {
        /** @var DelayPenaltyRecordedPayload $payload */
        $payload = $event->payload;

        if ($payload->team === TeamAB::TeamA) {
            $state->delayPenaltiesTeamA++;
        } else {
            $state->delayPenaltiesTeamB++;
        }

        $this->awardRallyTo($state, $payload->awardedTeam);

        return $state;
    }

    private function applyTimeOutRequested(GameState $state, GameEvent $event): GameState
    {
        /** @var TimeOutRequestedPayload $payload */
        $payload = $event->payload;

        if ($payload->team === TeamAB::TeamA) {
            $state->timeoutsTeamA++;
        } else {
            $state->timeoutsTeamB++;
        }

        return $state;
    }

    private function applyImproperRequestRecorded(GameState $state, GameEvent $event): GameState
    {
        /** @var ImproperRequestRecordedPayload $payload */
        $payload = $event->payload;

        if ($payload->team === TeamAB::TeamA) {
            $state->improperRequestsTeamA++;
        } else {
            $state->improperRequestsTeamB++;
        }

        return $state;
    }

    private function applyDelayWarningRecorded(GameState $state, GameEvent $event): GameState
    {
        /** @var DelayWarningRecordedPayload $payload */
        $payload = $event->payload;

        if ($payload->team === TeamAB::TeamA) {
            $state->delayWarningsTeamA++;
        } else {
            $state->delayWarningsTeamB++;
        }

        return $state;
    }

    private function applyMisconductRecorded(GameState $state, GameEvent $event): GameState
    {
        /** @var MisconductRecordedPayload $payload */
        $payload = $event->payload;
        $opponent = $payload->team === TeamAB::TeamA ? TeamAB::TeamB : TeamAB::TeamA;

        match ($payload->sanction) {
            MisconductSanction::Warning => $payload->team === TeamAB::TeamA
                ? $state->misconductWarningsTeamA++
                : $state->misconductWarningsTeamB++,
            MisconductSanction::Penalty => $payload->team === TeamAB::TeamA
                ? $state->misconductPenaltiesTeamA++
                : $state->misconductPenaltiesTeamB++,
            MisconductSanction::Expulsion => $payload->team === TeamAB::TeamA
                ? $state->misconductExpulsionsTeamA++
                : $state->misconductExpulsionsTeamB++,
            MisconductSanction::Disqualification => $payload->team === TeamAB::TeamA
                ? $state->misconductDisqualificationsTeamA++
                : $state->misconductDisqualificationsTeamB++,
        };

        if ($payload->sanction === MisconductSanction::Penalty) {
            $this->awardRallyTo($state, $opponent);
        }

        return $state;
    }

    private function applySetStarted(GameState $state, GameEvent $event): GameState
    {
        $state->setNumber = max(1, $state->setNumber + 1);
        $state->resetCurrentSetCounters();

        if ($state->setNumber !== 5 || $state->fifthSetLeftTeam === null) {
            $state->servingTeam = $this->servingTeamForSet($state->setNumber) ?? $state->servingTeam;
        }

        $state->setInProgress = true;

        return $state;
    }

    private function applySetEnded(GameState $state, GameEvent $event): GameState
    {
        if ($state->scoreTeamA > $state->scoreTeamB) {
            $state->setsWonTeamA++;
        } elseif ($state->scoreTeamB > $state->scoreTeamA) {
            $state->setsWonTeamB++;
        }

        $nextSetNumber = max(1, $state->setNumber + 1);

        if ($nextSetNumber === 5 && $state->setsWonTeamA === 2 && $state->setsWonTeamB === 2) {
            $state->servingTeam = null;
            $state->fifthSetLeftTeam = null;
            $state->fifthSetSideSwapped = false;
        } else {
            $state->servingTeam = $this->servingTeamForSet($nextSetNumber) ?? $state->servingTeam;
        }

        $state->scoreTeamA = 0;
        $state->scoreTeamB = 0;
        $state->timeoutsTeamA = 0;
        $state->timeoutsTeamB = 0;
        $state->rotationTeamA = [];
        $state->rotationTeamB = [];
        $state->setInProgress = false;

        return $state;
    }

    private function applyGameEnded(GameState $state): GameState
    {
        $state->setInProgress = false;
        $state->gameEnded = true;

        return $state;
    }

    private function rotateTeam(GameState $state, TeamAB $team): void
    {
        if ($team === TeamAB::TeamA) {
            $state->rotationTeamA = $this->rotate($state->rotationTeamA);

            return;
        }

        $state->rotationTeamB = $this->rotate($state->rotationTeamB);
    }

    private function awardRallyTo(GameState $state, TeamAB $winner): void
    {
        if ($winner === TeamAB::TeamA) {
            $state->scoreTeamA++;
        } else {
            $state->scoreTeamB++;
        }

        if ($state->servingTeam !== null && $state->servingTeam !== $winner) {
            $this->rotateTeam($state, $winner);
        }

        $state->servingTeam = $winner;
    }

    /**
     * @param  array<int, int>  $positions
     * @return array<int, int>
     */
    private function rotate(array $positions): array
    {
        $expected = range(1, 6);

        if (array_keys($positions) !== $expected) {
            return $positions;
        }

        return [
            1 => $positions[2],
            2 => $positions[3],
            3 => $positions[4],
            4 => $positions[5],
            5 => $positions[6],
            6 => $positions[1],
        ];
    }

    /**
     * @param  array<int, int>  $positions
     * @return array<int, int>
     */
    private function substitute(array $positions, int $playerOut, int $playerIn): array
    {
        $position = array_search($playerOut, $positions, true);

        if ($position === false) {
            return $positions;
        }

        $positions[$position] = $playerIn;

        return $positions;
    }

    private function servingTeamForSet(int $setNumber): ?TeamAB
    {
        $tossServingTeam = $this->tossServingTeam();

        if ($tossServingTeam === null) {
            return null;
        }

        return $setNumber % 2 === 1
            ? $tossServingTeam
            : $this->oppositeTeam($tossServingTeam);
    }

    private function tossServingTeam(): ?TeamAB
    {
        if ($this->resolvedTossServingTeam) {
            return $this->tossServingTeam;
        }

        $game = Game::current();

        /** @var GameEvent|null $tossEvent */
        $tossEvent = $game->events()
            ->reorder()
            ->where('type', GameEventType::TossCompleted)
            ->orderBy('created_at')
            ->orderBy('id')
            ->first();

        if ($tossEvent === null || ! $tossEvent->payload instanceof TossCompletedPayload) {
            $this->resolvedTossServingTeam = true;
            $this->tossServingTeam = null;

            return null;
        }

        $this->resolvedTossServingTeam = true;
        $this->tossServingTeam = $tossEvent->payload->serving;

        return $this->tossServingTeam;
    }

    private function oppositeTeam(TeamAB $team): TeamAB
    {
        return $team === TeamAB::TeamA
            ? TeamAB::TeamB
            : TeamAB::TeamA;
    }

    private function requiresFifthSetToss(GameState $state): bool
    {
        return ! $state->gameEnded
            && ! $state->setInProgress
            && $state->setNumber === 4
            && $state->setsWonTeamA === 2
            && $state->setsWonTeamB === 2;
    }
}
