<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Enums\GameEventType;
use App\Enums\TeamAB;
use App\Events\Payloads\TimeOutRequestedPayload;
use App\Models\Game;
use App\Services\GameState\GameEventRuleValidator;

/**
 * @mixin Game
 */
trait RecordsTimeOut
{
    public function recordTimeOut(TeamAB $team): void
    {
        app(GameEventRuleValidator::class)->assertCanRecordTimeOut($this);

        $this->events()->create([
            'type' => GameEventType::TimeOutRequested,
            'payload' => new TimeOutRequestedPayload(
                team: $team,
            ),
        ]);
    }
}
