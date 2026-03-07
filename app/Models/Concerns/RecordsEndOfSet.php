<?php

namespace App\Models\Concerns;

use App\Enums\GameEventType;
use App\Events\Payloads\SetEndedPayload;
use App\Services\GameState\GameEventRuleValidator;

/**
 * @mixin \App\Models\Game
 */
trait RecordsEndOfSet
{
    public function recordSetEnded(): void
    {
        app(GameEventRuleValidator::class)->assertCanRecordSetEnded($this);

        $this->events()->create([
            'type' => GameEventType::SetEnded,
            'payload' => new SetEndedPayload,
        ]);
    }
}
