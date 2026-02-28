<?php

namespace App\Models\Concerns;

use App\Enums\GameEventType;
use App\Events\Payloads\SetWonPayload;

/**
 * @mixin \App\Models\Game
 */
trait RecordsSetWon
{
    public function recordSetWon(): void
    {
        $this->events()->create([
            'type' => GameEventType::SetWon,
            'payload' => new SetWonPayload,
        ]);
    }
}
