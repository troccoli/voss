<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Enums\GameEventType;
use App\Enums\TeamAB;
use App\Enums\TeamSide;
use App\Events\Payloads\LineupSubmittedPayload;
use App\Events\Payloads\TossCompletedPayload;
use App\Models\Game;
use App\Models\GameEvent;
use App\Services\GameState\GameEventRuleValidator;
use Illuminate\Support\Collection;
use LogicException;

/**
 * @mixin Game
 */
trait RecordsLineup
{
    /** @param array<int, int> $positions */
    public function recordLineup(int $set, TeamAB $team, array $positions): void
    {
        app(GameEventRuleValidator::class)->assertCanRecordLineup($this, $set);

        $tossPayload = $this->getLatestTossPayload();
        $validRosterNumbers = $this->resolveRosterNumbersForTeam($team, $tossPayload);

        $this->events()->create([
            'type' => GameEventType::LineupSubmitted,
            'payload' => LineupSubmittedPayload::create(
                set: $set,
                team: $team,
                positions: $positions,
                validRosterNumbers: $validRosterNumbers,
            ),
        ]);
    }

    private function getLatestTossPayload(): TossCompletedPayload
    {
        /** @var GameEvent|null $tossEvent */
        $tossEvent = $this->events()
            ->where('type', GameEventType::TossCompleted)
            ->latest()
            ->first();

        if ($tossEvent === null) {
            throw new LogicException('A lineup cannot be submitted before the toss has been recorded.');
        }

        /** @var TossCompletedPayload */
        return $tossEvent->payload;
    }

    /**
     * @return Collection<int, int>
     */
    private function resolveRosterNumbersForTeam(TeamAB $team, TossCompletedPayload $tossPayload): Collection
    {
        $side = $team === TeamAB::TeamA
            ? $tossPayload->teamA
            : ($tossPayload->teamA === TeamSide::Home ? TeamSide::Away : TeamSide::Home);

        return $side === TeamSide::Home
            ? $this->homePlayers()->wherePivot('is_libero', false)->pluck('game_player.number')
            : $this->awayPlayers()->wherePivot('is_libero', false)->pluck('game_player.number');
    }
}
