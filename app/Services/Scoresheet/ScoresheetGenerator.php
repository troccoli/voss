<?php

namespace App\Services\Scoresheet;

use App\Models\Game;

class ScoresheetGenerator
{
    public function __construct(
        protected ScoresheetWriterFactory $writerFactory
    ) {}

    public function generate(Game $game): ScoresheetPdf
    {
        $pdf = new ScoresheetPdf;

        $path = storage_path('app/private/FIVB_VB_OfficialScoresheet_2013_updated2.pdf');
        $pdf->setSourceFile($path);
        $pdf->SetMargins(0, 0, 0);
        $pdf->SetAutoPageBreak(false);

        $templateId = $pdf->importPage(1);

        $pdf->AddPage();
        $pdf->useTemplate($templateId, adjustPageSize: true);

        $pdf->SetDisplayMode(zoom: 'fullpage', layout: 'single');
        $pdf->SetFont(family: 'Courier', size: 12);

        // Use the factory to get writers and execute them
        $this->writerFactory->make('match_info')->write($pdf, $game);
        $this->writerFactory->make('teams')->write($pdf, $game);
        $this->writerFactory->make('officials')->write($pdf, $game);

        return $pdf;
    }
}
