<?php

namespace App\Models\Concerns;

use App\Enums\GameEventType;
use App\Events\Payloads\GameEndedPayload;

/**
 * @mixin \App\Models\Game
 */
trait RecordsEndOfGame
{
    public function recordGameEnded(): void
    {
        $this->events()->create([
            'type' => GameEventType::GameEnded,
            'payload' => new GameEndedPayload,
        ]);
    }
}
