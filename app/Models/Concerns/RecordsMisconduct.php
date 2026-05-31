<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Enums\GameEventType;
use App\Enums\MisconductSanction;
use App\Enums\MisconductSubjectType;
use App\Enums\TeamAB;
use App\Events\Payloads\MisconductRecordedPayload;
use App\Models\Game;
use App\Services\GameState\GameEventRuleValidator;

/**
 * @mixin Game
 */
trait RecordsMisconduct
{
    public function recordMisconduct(
        TeamAB $team,
        MisconductSubjectType $subjectType,
        int $subjectId,
        MisconductSanction $sanction,
    ): void {
        app(GameEventRuleValidator::class)->assertCanRecordMisconduct($this, $team, $subjectType, $subjectId, $sanction);

        $this->events()->create([
            'type' => GameEventType::MisconductRecorded,
            'payload' => new MisconductRecordedPayload(
                team: $team,
                subjectType: $subjectType,
                subjectId: $subjectId,
                sanction: $sanction,
            ),
        ]);
    }
}
