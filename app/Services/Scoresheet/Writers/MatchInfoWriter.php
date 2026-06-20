<?php

declare(strict_types=1);

namespace App\Services\Scoresheet\Writers;

use App\Models\Game;
use App\Services\Scoresheet\Contracts\ScoresheetSectionWriter;
use App\Services\Scoresheet\ScoresheetPdf;
use App\Services\ScoresheetDataRepository;

class MatchInfoWriter implements ScoresheetSectionWriter
{
    public function __construct(
        private readonly ScoresheetDataRepository $scoresheetDataRepository
    ) {}

    public function write(ScoresheetPdf $pdf, Game $game): void
    {
        $matchInfo = $this->scoresheetDataRepository->matchInfo($game);

        // Name of the competition
        $pdf->SetXY(65, 16);
        $pdf->Write(0, $matchInfo['competition_name']);

        // City, Country Code, Hall, Pool, Match number
        $pdf->spacedPrint(27, 23, $matchInfo['city']);
        $pdf->spacedPrint(140, 23, $matchInfo['country_code']);
        $pdf->spacedPrint(27, 29, $matchInfo['hall']);
        $pdf->spacedPrint(111, 29, $matchInfo['pool']);
        $pdf->spacedPrint(146, 29, (string) $matchInfo['match_number']);

        // Date and time
        $pdf->spacedPrint(170, 23, $matchInfo['scheduled_at']->format('dmy'));
        $pdf->spacedPrint(220, 23, $matchInfo['scheduled_at']->format('Hi'));

        // Division and category
        if ($matchInfo['division'] === 'Men') {
            $this->cross($pdf, 41, 34);
        } else {
            $this->cross($pdf, 63, 34);
        }

        if ($matchInfo['category'] === 'Senior') {
            $this->cross($pdf, 111, 34);
        } elseif ($matchInfo['category'] === 'Junior') {
            $this->cross($pdf, 132, 34);
        } else {
            $this->cross($pdf, 152, 34);
        }

        // Home and away teams
        $pdf->spacedPrint(175, 35, $matchInfo['home_team_code']);
        $pdf->spacedPrint(214, 35, $matchInfo['away_team_code']);
    }

    private function cross(ScoresheetPdf $pdf, int $x, int $y): void
    {
        $pdf->SetLineWidth(0.5);
        $pdf->Line($x, $y, $x + 4, $y + 4);
        $pdf->Line($x + 4, $y, $x, $y + 4);
    }
}
