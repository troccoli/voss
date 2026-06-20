<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\CurrentMatchResolver;
use App\Services\GameState\GameStateRecalculator;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RecalculateGameStateSnapshots implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ?string $upTo = null,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(GameStateRecalculator $recalculator, CurrentMatchResolver $currentMatchResolver): void
    {
        $upTo = $this->upTo === null
            ? null
            : CarbonImmutable::parse($this->upTo);

        $recalculator->recalculate($currentMatchResolver->currentOrFail(), $upTo);
    }
}
