<?php

namespace App\Models\Concerns;

use App\Enums\GameEventType;
use App\Enums\TeamAB;
use App\Events\Payloads\SubstitutionCompletedPayload;

/**
 * @mixin \App\Models\Game
 */
trait RecordsSubstitution
{
    public function recordSubstitution(TeamAB $team, int $playerOut, int $playerIn): void
    {
        $this->events()->create([
            'type' => GameEventType::SubstitutionCompleted,
            'payload' => new SubstitutionCompletedPayload(
                team: $team,
                playerOut: $playerOut,
                playerIn: $playerIn,
            ),
        ]);
    }
}
