<?php

namespace App\Models\Concerns;

use App\Enums\GameEventType;
use App\Events\Payloads\GameWonPayload;

/**
 * @mixin \App\Models\Game
 */
trait RecordsGameWon
{
    public function recordGameWon(): void
    {
        $this->events()->create([
            'type' => GameEventType::GameWon,
            'payload' => new GameWonPayload,
        ]);
    }
}
