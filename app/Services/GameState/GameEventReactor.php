<?php

namespace App\Services\GameState;

use App\Enums\GameEventType;
use App\Events\Payloads\GameEndedPayload;
use App\Events\Payloads\GameEventPayload;
use App\Events\Payloads\SetEndedPayload;
use App\Models\GameEvent;

class GameEventReactor
{
    public function reactTo(GameEvent $event): void
    {
        match ($event->type) {
            GameEventType::RallyEnded => $this->onRallyEnded($event),
            GameEventType::SetEnded => $this->onSetEnded($event),
            default => null,
        };
    }

    protected function onRallyEnded(GameEvent $event): void
    {
        $state = $event->game->stateAt();

        if (! $state->setInProgress) {
            return;
        }

        $targetPoints = $state->setNumber === 5 ? 15 : 25;
        $highestScore = max($state->scoreTeamA, $state->scoreTeamB);
        $scoreDifference = abs($state->scoreTeamA - $state->scoreTeamB);

        if ($highestScore < $targetPoints || $scoreDifference < 2) {
            return;
        }

        $this->trigger(
            sourceEvent: $event,
            type: GameEventType::SetEnded,
            payload: new SetEndedPayload,
        );
    }

    protected function onSetEnded(GameEvent $event): void
    {
        $state = $event->game->stateAt();

        if ($state->setsWonTeamA < 3 && $state->setsWonTeamB < 3) {
            return;
        }

        $this->trigger(
            sourceEvent: $event,
            type: GameEventType::GameEnded,
            payload: new GameEndedPayload,
        );
    }

    private function trigger(GameEvent $sourceEvent, GameEventType $type, GameEventPayload $payload): void
    {
        $sourceEvent->game->events()->create([
            'type' => $type,
            'payload' => $payload,
        ]);
    }
}
