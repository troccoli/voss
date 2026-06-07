<?php

declare(strict_types=1);

namespace App\Services\GameState;

use App\Data\GameState\GameState;
use App\Models\Game;
use App\Models\GameEvent;
use App\Models\GameStateSnapshot;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

class GameStateRecalculator
{
    public function __construct(
        protected GameStateProjector $projector
    ) {}

    public function recalculate(Game $game, ?CarbonImmutable $upTo = null): void
    {
        GameStateSnapshot::query()
            ->where('game_id', $game->getKey())
            ->delete();

        $events = GameEvent::query()
            ->where('game_id', $game->getKey())
            ->when($upTo !== null, fn (Builder $query): Builder => $query->where('created_at', '<=', $upTo))
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        $state = GameState::initial();

        /** @var GameEvent $event */
        foreach ($events as $event) {
            $state = $this->projector->project($state, $event);

            GameStateSnapshot::query()->create([
                'game_id' => $game->getKey(),
                'game_event_id' => $event->getKey(),
                ...$state->toAttributes(),
                'created_at' => $event->created_at,
            ]);
        }
    }
}
