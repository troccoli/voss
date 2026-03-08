<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Game;
use App\Services\Scoresheet\ScoresheetGenerator;
use Illuminate\Console\Command;

class GeneratePdf extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    #[\Override]
    protected $signature = 'app:generate-pdf';

    /**
     * The console command description.
     *
     * @var string
     */
    #[\Override]
    protected $description = 'Command description';

    public function handle(ScoresheetGenerator $generator): void
    {
        /** @var Game $game */
        $game = Game::query()->first();

        $pdf = $generator->generate($game);

        $pdf->Output('F', storage_path('app/public/scoresheet.pdf'));
    }
}
