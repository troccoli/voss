<?php

declare(strict_types=1);

namespace App\Services\GameState;

use App\Data\GameState\GameState;
use App\Enums\GameEventType;
use App\Enums\TeamAB;
use App\Events\Payloads\LineupSubmittedPayload;
use App\Events\Payloads\RallyEndedPayload;
use App\Events\Payloads\SubstitutionCompletedPayload;
use App\Events\Payloads\TimeOutRequestedPayload;
use App\Events\Payloads\TossCompletedPayload;
use App\Models\GameEvent;
use App\Models\GameStateSnapshot;
use Illuminate\Database\Eloquent\Builder;

class GameStateProjector
{
    public function project(GameState $state, GameEvent $event): GameState
    {
        return match ($event->type) {
            GameEventType::TossCompleted => $this->applyTossCompleted($state, $event),
            GameEventType::LineupSubmitted => $this->applyLineupSubmitted($state, $event),
            GameEventType::RallyEnded => $this->applyRallyEnded($state, $event),
            GameEventType::SubstitutionCompleted => $this->applySubstitutionCompleted($state, $event),
            GameEventType::TimeOutRequested => $this->applyTimeOutRequested($state, $event),
            GameEventType::SetStarted => $this->applySetStarted($state),
            GameEventType::SetEnded => $this->applySetEnded($state),
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
        $state->servingTeam = $payload->serving;

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
        $winner = $payload->team;

        if ($winner === TeamAB::TeamA) {
            $state->scoreTeamA++;
        } else {
            $state->scoreTeamB++;
        }

        if ($state->servingTeam !== null && $state->servingTeam !== $winner) {
            $this->rotateTeam($state, $winner);
        }

        $state->servingTeam = $winner;

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

    private function applySetStarted(GameState $state): GameState
    {
        $state->setNumber = max(1, $state->setNumber + 1);
        $state->resetCurrentSetCounters();
        $state->setInProgress = true;

        return $state;
    }

    private function applySetEnded(GameState $state): GameState
    {
        if ($state->scoreTeamA > $state->scoreTeamB) {
            $state->setsWonTeamA++;
        } elseif ($state->scoreTeamB > $state->scoreTeamA) {
            $state->setsWonTeamB++;
        }

        if ($state->servingTeam !== null) {
            $state->servingTeam = $state->servingTeam === TeamAB::TeamA
                ? TeamAB::TeamB
                : TeamAB::TeamA;
        }

        $state->scoreTeamA = 0;
        $state->scoreTeamB = 0;
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
}
