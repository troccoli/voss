<?php

namespace App\Models\Concerns;

use App\Enums\GameEventType;
use App\Events\Payloads\SetStartedPayload;

/**
 * @mixin \App\Models\Game
 */
trait RecordsStartOfSet
{
    public function recordSetStarted(): void
    {
        $this->events()->create([
            'type' => GameEventType::SetStarted,
            'payload' => new SetStartedPayload,
        ]);
    }
}
