<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Enums\GameEventType;
use App\Events\Payloads\GameEndedPayload;
use App\Services\GameState\GameEventRuleValidator;

/**
 * @mixin \App\Models\Game
 */
trait RecordsEndOfGame
{
    public function recordGameEnded(): void
    {
        app(GameEventRuleValidator::class)->assertCanRecordGameEnded($this);

        $this->events()->create([
            'type' => GameEventType::GameEnded,
            'payload' => new GameEndedPayload,
        ]);
    }
}
