<?php

namespace App\Models\Concerns;

use App\Enums\GameEventType;
use App\Enums\TeamAB;
use App\Events\Payloads\TimeOutRequestedPayload;

/**
 * @mixin \App\Models\Game
 */
trait RecordsTimeOut
{
    public function recordTimeOut(TeamAB $team): void
    {
        $this->events()->create([
            'type' => GameEventType::TimeOutRequested,
            'payload' => new TimeOutRequestedPayload(
                team: $team,
            ),
        ]);
    }
}
