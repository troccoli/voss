<?php

namespace App\Jobs;

use App\Models\Game;
use App\Services\GameState\GameStateRecalculator;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RecalculateGameStateSnapshots implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $gameId,
        public ?string $upTo = null,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(GameStateRecalculator $recalculator): void
    {
        /** @var Game $game */
        $game = Game::query()->findOrFail($this->gameId);

        $upTo = $this->upTo === null
            ? null
            : CarbonImmutable::parse($this->upTo);

        $recalculator->recalculate($game, $upTo);
    }
}
