<?php

namespace App\Models\Concerns;

use App\Enums\GameEventType;
use App\Enums\TeamAB;
use App\Events\Payloads\RallyEndedPayload;

/**
 * @mixin \App\Models\Game
 */
trait RecordsEndOfRally
{
    public function recordRallyWinner(TeamAB $team): void
    {
        $this->events()->create([
            'type' => GameEventType::RallyEnded,
            'payload' => new RallyEndedPayload(
                team: $team,
            ),
        ]);
    }
}
