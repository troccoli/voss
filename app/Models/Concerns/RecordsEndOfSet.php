<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Enums\GameEventType;
use App\Events\Payloads\SetEndedPayload;
use App\Models\Game;
use App\Services\GameState\GameEventRuleValidator;

/**
 * @mixin Game
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
