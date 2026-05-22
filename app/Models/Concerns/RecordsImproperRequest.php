<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Enums\GameEventType;
use App\Enums\ImproperRequestType;
use App\Enums\TeamAB;
use App\Events\Payloads\ImproperRequestRecordedPayload;
use App\Models\Game;
use App\Services\GameState\GameEventRuleValidator;

/**
 * @mixin Game
 */
trait RecordsImproperRequest
{
    public function recordImproperRequest(TeamAB $team, ImproperRequestType $requestType): void
    {
        app(GameEventRuleValidator::class)->assertCanRecordImproperRequest($this);

        $this->events()->create([
            'type' => GameEventType::ImproperRequestRecorded,
            'payload' => new ImproperRequestRecordedPayload(
                team: $team,
                requestType: $requestType,
            ),
        ]);
    }
}
