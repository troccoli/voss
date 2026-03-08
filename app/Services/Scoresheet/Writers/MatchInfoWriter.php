<?php

declare(strict_types=1);

namespace App\Services\Scoresheet\Writers;

use App\Models\Game;
use App\Services\Scoresheet\Contracts\ScoresheetSectionWriter;
use App\Services\Scoresheet\ScoresheetPdf;

class MatchInfoWriter implements ScoresheetSectionWriter
{
    public function write(ScoresheetPdf $pdf, Game $game): void
    {
        // Name of the competition
        $pdf->SetXY(65, 16);
        $pdf->Write(0, $game->championship->name);

        // City, Country Code, Hall, Pool, Match number
        $pdf->spacedPrint(27, 23, $game->city);
        $pdf->spacedPrint(140, 23, $game->country_code);
        $pdf->spacedPrint(27, 29, $game->hall);
        $pdf->spacedPrint(111, 29, $game->pool);
        $pdf->spacedPrint(146, 29, (string) $game->number);

        // Date and time
        $pdf->spacedPrint(170, 23, $game->date_time->format('dmy'));
        $pdf->spacedPrint(220, 23, $game->date_time->format('hi'));

        // Division and category
        if ($game->division === 'Men') {
            $this->cross($pdf, 41, 34);
        } else {
            $this->cross($pdf, 63, 34);
        }

        if ($game->category === 'Senior') {
            $this->cross($pdf, 111, 34);
        } elseif ($game->category === 'Junior') {
            $this->cross($pdf, 132, 34);
        } else {
            $this->cross($pdf, 152, 34);
        }

        // Home and away teams
        $pdf->spacedPrint(175, 35, $game->homeTeam->country_code);
        $pdf->spacedPrint(214, 35, $game->awayTeam->country_code);
    }

    private function cross(ScoresheetPdf $pdf, int $x, int $y): void
    {
        $pdf->SetLineWidth(0.5);
        $pdf->Line($x, $y, $x + 4, $y + 4);
        $pdf->Line($x + 4, $y, $x, $y + 4);
    }
}
