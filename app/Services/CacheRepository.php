<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\GameEventType;
use App\Enums\TeamSide;
use App\Events\Payloads\TossCompletedPayload;
use App\Models\Game;
use App\Models\GameEvent;
use App\Models\Player;

class CacheRepository
{
    /**
     * @return array<int, array{
     *     player_key: int,
     *      number: int,
     *     last_name: string
     * }>
     */
    public function playersForSide(Game $game, TeamSide $side): array
    {
        $players = $side === TeamSide::Home
            ? $game->homePlayers()
            : $game->awayPlayers();

        return $players
            ->wherePivot('is_libero', false)
            ->orderByPivot('number')
            ->get()
            ->map(fn (Player $player): array => [
                'player_key' => $player->getKey(),
                'number' => $player->roster->number,
                'last_name' => $player->last_name,
            ])
            ->all();
    }

    public function latestTossPayload(Game $game): ?TossCompletedPayload
    {
        /** @var GameEvent|null $tossEvent */
        $tossEvent = $game->events()
            ->reorder()
            ->where('type', GameEventType::TossCompleted)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first();

        if ($tossEvent === null) {
            return null;
        }

        /** @var TossCompletedPayload */
        return $tossEvent->payload;
    }
}
