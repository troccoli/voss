<?php

namespace App\Services\Scoresheet\Writers;

use App\Enums\StaffRole;
use App\Models\Game;
use App\Models\Player;
use App\Models\Staff;
use App\Services\Scoresheet\Contracts\ScoresheetSectionWriter;
use App\Services\Scoresheet\ScoresheetPdf;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class TeamsWriter implements ScoresheetSectionWriter
{
    public function write(ScoresheetPdf $pdf, Game $game): void
    {
        $pdf->SetFontSize(12);
        $pdf->spacedPrint(341, 160, $game->homeTeam->country_code);
        $pdf->spacedPrint(375, 160, $game->awayTeam->country_code);

        $this->writePlayers($pdf, $game->homePlayers, 327);
        $this->writePlayers($pdf, $game->awayPlayers, 367);

        $this->writeStaff($pdf, $game->homeStaff, 327);
        $this->writeStaff($pdf, $game->awayStaff, 372);
    }

    /**
     * @param  EloquentCollection<int, Player>  $players
     */
    private function writePlayers(ScoresheetPdf $pdf, EloquentCollection $players, int $x): void
    {
        $y = [
            171, 175, 180, 184, 188, 193, 197, 201, 206, 210, 214, 219, 223, 227,
        ];
        $pdf->SetFontSize(8);
        $liberos = $players
            ->filter(fn (Player $player) => $player->roster->is_libero)
            ->sortBy('roster.number')
            ->values();
        $players = $players
            ->reject(fn (Player $player) => $player->roster->is_libero)
            ->sortBy('roster.number')
            ->values();
        foreach ($players as $i => $player) {
            $pdf->SetXY($x, $y[$i]);
            if ($player->roster->number < 10) {
                $pdf->SetX($x + 1);
            }
            $pdf->Write(0, $player->roster->number);

            $pdf->SetXY($x + 5, $y[$i]);
            $pdf->Write(0, $this->name($player));

            if ($player->roster->is_captain) {
                $pdf->circle($x + 2.5, $y[$i] - 0.45, 2.2, 0.4);
            }
        }

        $y = 236;
        foreach ($liberos as $libero) {
            $pdf->SetXY($x, $y);
            if ($libero->roster->number < 10) {
                $pdf->SetX($x + 1);
            }
            $pdf->Write(0, $libero->roster->number);

            $pdf->SetXY($x + 5, $y);
            $pdf->Write(0, $this->name($libero));

            $y += 4;
        }
    }

    /**
     * @param  EloquentCollection<int, Staff>  $staff
     */
    private function writeStaff(ScoresheetPdf $pdf, EloquentCollection $staff, int $x): void
    {
        $firstAssistantCoach = true;
        $pdf->SetFontSize(8);
        foreach ($staff as $staffMember) {
            $y = match ($staffMember->roster->role) {
                StaffRole::Coach => 248,
                StaffRole::AssistantCoach => $firstAssistantCoach ? 252 : 256,
                StaffRole::Therapist => 260,
                StaffRole::Doctor => 264,
            };
            $pdf->SetXY($x, $y);
            $pdf->Write(0, $this->name($staffMember));
            if ($staffMember->roster->role === StaffRole::AssistantCoach) {
                $firstAssistantCoach = false;
            }
        }
    }

    private function name(Player|Staff $playerOrStaff): string
    {
        $lastName = str($playerOrStaff->last_name)->upper();
        $initial = str($playerOrStaff->first_name)->upper()->charAt(0);

        return "$lastName, $initial.";
    }
}
