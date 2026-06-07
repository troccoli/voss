<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Enums\GameEventType;
use App\Events\Payloads\CourtSidesSwappedPayload;
use App\Models\Game;
use App\Services\GameState\GameEventRuleValidator;

/**
 * @mixin Game
 */
trait RecordsCourtSideSwap
{
    public function recordCourtSidesSwapped(): void
    {
        app(GameEventRuleValidator::class)->assertCanRecordCourtSidesSwapped($this);

        $this->events()->create([
            'type' => GameEventType::CourtSidesSwapped,
            'payload' => new CourtSidesSwappedPayload,
        ]);
    }
}
