<?php

declare(strict_types=1);

namespace App\Services\Scoresheet\Writers;

use App\Models\Game;
use App\Services\Scoresheet\Contracts\ScoresheetSectionWriter;
use App\Services\Scoresheet\ScoresheetPdf;
use App\Services\ScoresheetDataRepository;

class ResultsWriter implements ScoresheetSectionWriter
{
    public function __construct(
        private readonly ScoresheetDataRepository $scoresheetDataRepository
    ) {}

    public function write(ScoresheetPdf $pdf, Game $game): void
    {
        $results = $this->scoresheetDataRepository->results($game);

        $pdf->SetFontSize(10);
        $pdf->spacedPrint(271, 283, $results['team_a_code']);
        $pdf->spacedPrint(398, 283, $results['team_b_code']);

        foreach ($results['sets'] as $index => $setSummary) {
            $y = 294 + ($index * 10);

            $this->writeCellValue($pdf, 251, $y, $setSummary['team_a_timeouts']);
            $this->writeCellValue($pdf, 260, $y, $setSummary['team_a_substitutions']);
            $this->writeCellValue($pdf, 269, $y, $setSummary['team_a_sets_won']);
            $this->writeCellValue($pdf, 280, $y, $setSummary['team_a_points']);
            $this->writeCellValue($pdf, 307, $y, $setSummary['set_number']);
            $this->writeCellValue($pdf, 322, $y, $setSummary['duration_minutes']);
            $this->writeCellValue($pdf, 347, $y, $setSummary['team_b_points']);
            $this->writeCellValue($pdf, 359, $y, $setSummary['team_b_sets_won']);
            $this->writeCellValue($pdf, 369, $y, $setSummary['team_b_substitutions']);
            $this->writeCellValue($pdf, 379, $y, $setSummary['team_b_timeouts']);
        }

        if ($results['match_start_time'] !== null) {
            $pdf->spacedPrint(245, 369, $results['match_start_time']->format('Hi'));
        }

        if ($results['match_end_time'] !== null) {
            $pdf->spacedPrint(311, 369, $results['match_end_time']->format('Hi'));
        }

        if ($results['total_duration_minutes'] !== null) {
            $hours = intdiv($results['total_duration_minutes'], 60);
            $minutes = $results['total_duration_minutes'] % 60;

            $this->writeCellValue($pdf, 386, 369, $hours);
            $this->writeCellValue($pdf, 402, 369, $minutes);
        }

        $this->writeCellValue($pdf, 325, 354, $results['total_set_duration_minutes']);
        $pdf->spacedPrint(300, 380, $results['winner_team_code']);
        $pdf->SetXY(389, 380);
        $pdf->Write(0, sprintf('%d : %d', $results['team_a_sets_won'], $results['team_b_sets_won']));
        $pdf->SetFontSize(12);
    }

    private function writeCellValue(ScoresheetPdf $pdf, int $x, int $y, int $value): void
    {
        $pdf->SetXY($value < 10 ? $x + 1 : $x, $y);
        $pdf->Write(0, (string) $value);
    }
}
