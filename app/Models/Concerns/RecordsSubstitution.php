<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Enums\GameEventType;
use App\Enums\TeamAB;
use App\Events\Payloads\SubstitutionCompletedPayload;
use App\Models\Game;
use App\Services\GameState\GameEventRuleValidator;

/**
 * @mixin Game
 */
trait RecordsSubstitution
{
    public function recordSubstitution(TeamAB $team, int $playerOut, int $playerIn): void
    {
        app(GameEventRuleValidator::class)->assertCanRecordSubstitution($this);

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
