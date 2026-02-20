<?php

namespace App\Services\Scoresheet\Writers;

use App\Enums\OfficialRole;
use App\Models\Game;
use App\Models\Official;
use App\Services\Scoresheet\Contracts\ScoresheetSectionWriter;
use App\Services\Scoresheet\ScoresheetPdf;

class OfficialsWriter implements ScoresheetSectionWriter
{
    public function write(ScoresheetPdf $pdf, Game $game): void
    {
        $pdf->SetFontSize(10);
        /** @var Official $official */
        foreach ($game->officials as $official) {
            [$x, $y] = match ($official->assignment->role) {
                OfficialRole::FirstReferee => [93, 253],
                OfficialRole::SecondReferee => [93, 259],
                OfficialRole::Scorer => [93, 265],
                OfficialRole::AssistantScorer => [93, 270],
                OfficialRole::LineJudge1 => [81, 276],
                OfficialRole::LineJudge2 => [173, 276],
                OfficialRole::LineJudge3 => [81, 281],
                OfficialRole::LineJudge4 => [173, 281],
            };
            $pdf->SetXY($x, $y);

            $pdf->Write(0, str($official->first_name.' '.$official->last_name)->upper());

            if (in_array($official->assignment->role, [
                OfficialRole::FirstReferee, OfficialRole::SecondReferee, OfficialRole::Scorer,
                OfficialRole::AssistantScorer,
            ])) {
                [$x, $y] = match ($official->assignment->role) {
                    OfficialRole::FirstReferee => [169, 253],
                    OfficialRole::SecondReferee => [169, 259],
                    OfficialRole::Scorer => [169, 265],
                    OfficialRole::AssistantScorer => [169, 270],
                };

                $pdf->SetXY($x, $y);
                $pdf->Write(0, $official->country_code);
            }
        }

        $pdf->SetFontSize(12);
    }
}
