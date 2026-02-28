<?php

namespace App\Models\Concerns;

use App\Enums\GameEventType;
use App\Enums\TeamAB;
use App\Events\Payloads\RallyWonPayload;

/**
 * @mixin \App\Models\Game
 */
trait RecordsRallyWon
{
    public function recordRallyWon(TeamAB $team): void
    {
        $this->events()->create([
            'type' => GameEventType::RallyWon,
            'payload' => new RallyWonPayload(
                team: $team,
            ),
        ]);
    }
}
