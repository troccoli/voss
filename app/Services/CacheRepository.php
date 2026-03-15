<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\GameEventType;
use App\Enums\TeamSide;
use App\Events\Payloads\TossCompletedPayload;
use App\Models\Game;
use App\Models\GameEvent;
use App\Models\Player;
use Illuminate\Support\Facades\Cache;

class CacheRepository
{
    private const int PLAYERS_FOR_SIDE_CACHE_SECONDS = 60;

    private const int LATEST_TOSS_PAYLOAD_CACHE_SECONDS = 60;

    /**
     * @return array<int, array{
     *     player_key: int,
     *      number: int,
     *     last_name: string
     * }>
     */
    public function playersForSide(Game $game, TeamSide $side): array
    {
        return Cache::remember(
            sprintf('game:%d:roster:players-for-side:%s', $game->getKey(), $side->value),
            now()->addSeconds(self::PLAYERS_FOR_SIDE_CACHE_SECONDS),
            function () use ($game, $side): array {
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
        );
    }

    public function latestTossPayload(Game $game): ?TossCompletedPayload
    {
        return Cache::remember(
            sprintf('game:%d:events:latest-toss-payload', $game->getKey()),
            now()->addSeconds(self::LATEST_TOSS_PAYLOAD_CACHE_SECONDS),
            function () use ($game): ?TossCompletedPayload {
                /** @var GameEvent|null $tossEvent */
                $tossEvent = $game->events()
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
        );
    }
}
