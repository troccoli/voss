<?php

declare(strict_types=1);

namespace App\Services\Scoresheet;

class ScoresheetPdfFactory
{
    public function make(): ScoresheetPdf
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

        return $pdf;
    }
}
