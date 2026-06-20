<?php

declare(strict_types=1);

namespace App\Services\Scoresheet;

use App\Models\Game;

class ScoresheetGenerator
{
    public function __construct(
        protected ScoresheetWriterFactory $writerFactory,
        protected ScoresheetPdfFactory $pdfFactory,
    ) {}

    public function generate(Game $game): ScoresheetPdf
    {
        $game->assertCanGeneratePdf();

        $pdf = $this->pdfFactory->make();

        $this->writerFactory->make('match_info')->write($pdf, $game);
        $this->writerFactory->make('teams')->write($pdf, $game);
        $this->writerFactory->make('officials')->write($pdf, $game);
        $this->writerFactory->make('results')->write($pdf, $game);

        $game->markPdfGenerated();

        return $pdf;
    }
}
