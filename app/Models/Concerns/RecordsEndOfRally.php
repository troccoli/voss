<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Enums\GameEventType;
use App\Enums\TeamAB;
use App\Events\Payloads\RallyEndedPayload;
use App\Models\Game;
use App\Services\GameState\GameEventRuleValidator;

/**
 * @mixin Game
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
