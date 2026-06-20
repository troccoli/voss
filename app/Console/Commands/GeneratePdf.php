<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Game;
use App\Services\Scoresheet\ScoresheetGenerator;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Description('Command description')]
#[Signature('app:generate-pdf')]
class GeneratePdf extends Command
{
    public function handle(ScoresheetGenerator $generator): void
    {
        $game = Game::current();

        $pdf = $generator->generate($game);

        $pdf->Output('F', storage_path('app/public/scoresheet.pdf'));
    }
}
