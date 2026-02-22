<?php

namespace App\Models\Concerns;

use App\Enums\GameEventType;
use App\Enums\TeamAB;
use App\Enums\TeamSide;
use App\Events\Payloads\TossCompletedPayload;

/**
 * @mixin \App\Models\Game
 */
trait RecordsToss
{
    public function recordToss(TeamSide $teamA, TeamAB $serving): void
    {
        $this->events()->create([
            'type' => GameEventType::TossCompleted,
            'payload' => new TossCompletedPayload(
                teamA: $teamA,
                serving: $serving,
            ),
        ]);
    }
}
