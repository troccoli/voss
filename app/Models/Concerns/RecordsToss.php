<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Enums\GameEventType;
use App\Enums\TeamAB;
use App\Enums\TeamSide;
use App\Events\Payloads\TossCompletedPayload;
use App\Services\GameState\GameEventRuleValidator;

/**
 * @mixin \App\Models\Game
 */
trait RecordsToss
{
    public function recordToss(TeamSide $teamA, TeamAB $serving): void
    {
        app(GameEventRuleValidator::class)->assertCanRecordToss($this);

        $this->events()->create([
            'type' => GameEventType::TossCompleted,
            'payload' => new TossCompletedPayload(
                teamA: $teamA,
                serving: $serving,
            ),
        ]);
    }
}
