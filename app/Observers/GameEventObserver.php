<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\GameEvent;
use App\Services\GameState\GameEventReactor;
use App\Services\GameState\GameStateProjector;

class GameEventObserver
{
    public function created(GameEvent $gameEvent): void
    {
        resolve(GameStateProjector::class)->projectAndStore($gameEvent);
        resolve(GameEventReactor::class)->reactTo($gameEvent);
    }
}
