<?php

namespace App\Models\Concerns;

use App\Enums\GameEventType;
use App\Events\Payloads\SetEndedPayload;

/**
 * @mixin \App\Models\Game
 */
trait RecordsEndOfSet
{
    public function recordSetEnded(): void
    {
        $this->events()->create([
            'type' => GameEventType::SetEnded,
            'payload' => new SetEndedPayload,
        ]);
    }
}
