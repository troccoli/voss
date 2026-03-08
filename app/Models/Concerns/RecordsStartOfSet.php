<?php

namespace App\Models\Concerns;

use App\Enums\GameEventType;
use App\Events\Payloads\SetStartedPayload;
use App\Services\GameState\GameEventRuleValidator;

/**
 * @mixin \App\Models\Game
 */
trait RecordsStartOfSet
{
    public function recordSetStarted(): void
    {
        app(GameEventRuleValidator::class)->assertCanRecordSetStarted($this);

        $this->events()->create([
            'type' => GameEventType::SetStarted,
            'payload' => new SetStartedPayload,
        ]);
    }
}
