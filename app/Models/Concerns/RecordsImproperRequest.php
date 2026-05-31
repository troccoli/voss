<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Enums\GameEventType;
use App\Enums\ImproperRequestType;
use App\Enums\TeamAB;
use App\Events\Payloads\DelayPenaltyRecordedPayload;
use App\Events\Payloads\DelayWarningRecordedPayload;
use App\Events\Payloads\ImproperRequestRecordedPayload;
use App\Models\Game;
use App\Services\GameState\GameEventRuleValidator;

/**
 * @mixin Game
 */
trait RecordsImproperRequest
{
    public function recordImproperRequest(TeamAB $team, ImproperRequestType $requestType): GameEventType
    {
        app(GameEventRuleValidator::class)->assertCanRecordImproperRequest($this);

        if ($this->hasDelayWarningFor($team)) {
            $this->events()->create([
                'type' => GameEventType::DelayPenaltyRecorded,
                'payload' => new DelayPenaltyRecordedPayload(
                    team: $team,
                    awardedTeam: $this->opponentOf($team),
                    requestType: $requestType,
                ),
            ]);

            return GameEventType::DelayPenaltyRecorded;
        }

        if ($this->hasImproperRequestFor($team)) {
            $this->events()->create([
                'type' => GameEventType::DelayWarningRecorded,
                'payload' => new DelayWarningRecordedPayload(
                    team: $team,
                    requestType: $requestType,
                ),
            ]);

            return GameEventType::DelayWarningRecorded;
        }

        $this->events()->create([
            'type' => GameEventType::ImproperRequestRecorded,
            'payload' => new ImproperRequestRecordedPayload(
                team: $team,
                requestType: $requestType,
            ),
        ]);

        return GameEventType::ImproperRequestRecorded;
    }

    public function recordDelayWarning(TeamAB $team): void
    {
        app(GameEventRuleValidator::class)->assertCanRecordDelayWarning($this, $team);

        $this->events()->create([
            'type' => GameEventType::DelayWarningRecorded,
            'payload' => new DelayWarningRecordedPayload(
                team: $team,
            ),
        ]);
    }

    public function recordDelayPenalty(TeamAB $team): void
    {
        app(GameEventRuleValidator::class)->assertCanRecordDelayPenalty($this);

        $this->events()->create([
            'type' => GameEventType::DelayPenaltyRecorded,
            'payload' => new DelayPenaltyRecordedPayload(
                team: $team,
                awardedTeam: $this->opponentOf($team),
            ),
        ]);
    }

    private function hasDelayWarningFor(TeamAB $team): bool
    {
        $state = $this->stateAt();

        if ($team === TeamAB::TeamA) {
            return $state->delayWarningsTeamA + $state->delayPenaltiesTeamA > 0;
        }

        return $state->delayWarningsTeamB + $state->delayPenaltiesTeamB > 0;
    }

    private function hasImproperRequestFor(TeamAB $team): bool
    {
        $state = $this->stateAt();

        if ($team === TeamAB::TeamA) {
            return $state->improperRequestsTeamA + $state->delayWarningsTeamA > 0;
        }

        return $state->improperRequestsTeamB + $state->delayWarningsTeamB > 0;
    }

    private function opponentOf(TeamAB $team): TeamAB
    {
        return $team === TeamAB::TeamA ? TeamAB::TeamB : TeamAB::TeamA;
    }
}
