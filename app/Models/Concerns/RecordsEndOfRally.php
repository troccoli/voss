<?php

namespace App\Models\Concerns;

use App\Enums\GameEventType;
use App\Enums\TeamAB;
use App\Events\Payloads\RallyEndedPayload;
use App\Services\GameState\GameEventRuleValidator;

/**
 * @mixin \App\Models\Game
 */
trait RecordsEndOfRally
{
    public function recordRallyWinner(TeamAB $team): void
    {
        app(GameEventRuleValidator::class)->assertCanRecordRally($this);

        $this->events()->create([
            'type' => GameEventType::RallyEnded,
            'payload' => new RallyEndedPayload(
                team: $team,
            ),
        ]);
    }
}
