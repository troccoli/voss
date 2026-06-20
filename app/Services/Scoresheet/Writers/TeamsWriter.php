<?php

declare(strict_types=1);

namespace App\Services\Scoresheet\Writers;

use App\Enums\StaffRole;
use App\Enums\TeamSide;
use App\Models\Game;
use App\Services\Scoresheet\Contracts\ScoresheetSectionWriter;
use App\Services\Scoresheet\ScoresheetPdf;
use App\Services\ScoresheetDataRepository;

class TeamsWriter implements ScoresheetSectionWriter
{
    public function __construct(
        private readonly ScoresheetDataRepository $scoresheetDataRepository
    ) {}

    public function write(ScoresheetPdf $pdf, Game $game): void
    {
        $homeTeamSheet = $this->scoresheetDataRepository->teamSheet($game, TeamSide::Home);
        $awayTeamSheet = $this->scoresheetDataRepository->teamSheet($game, TeamSide::Away);

        $pdf->SetFontSize(12);
        $pdf->spacedPrint(341, 160, $homeTeamSheet['team_code']);
        $pdf->spacedPrint(375, 160, $awayTeamSheet['team_code']);

        $this->writePlayers($pdf, $homeTeamSheet['players'], $homeTeamSheet['liberos'], 327);
        $this->writePlayers($pdf, $awayTeamSheet['players'], $awayTeamSheet['liberos'], 367);

        $this->writeStaff($pdf, $homeTeamSheet['staff'], 327);
        $this->writeStaff($pdf, $awayTeamSheet['staff'], 372);
    }

    /**
     * @param  array<int, array{
     *     player_key: int,
     *     number: int,
     *     last_name: string,
     *     is_captain: bool
     * }>  $players
     * @param  array<int, array{
     *     player_key: int,
     *     number: int,
     *     last_name: string
     * }>  $liberos
     */
    private function writePlayers(ScoresheetPdf $pdf, array $players, array $liberos, int $x): void
    {
        $y = [
            171, 175, 180, 184, 188, 193, 197, 201, 206, 210, 214, 219, 223, 227,
        ];
        $pdf->SetFontSize(8);
        foreach ($players as $i => $player) {
            $pdf->SetXY($x, $y[$i]);
            if ($player['number'] < 10) {
                $pdf->SetX($x + 1);
            }
            $pdf->Write(0, (string) $player['number']);

            $pdf->SetXY($x + 5, $y[$i]);
            $pdf->Write(0, $this->formattedName($player['last_name']));

            if ($player['is_captain']) {
                $pdf->circle($x + 2.5, $y[$i] - 0.45, 2.2, 0.4);
            }
        }

        $y = 236;
        foreach ($liberos as $libero) {
            $pdf->SetXY($x, $y);
            if ($libero['number'] < 10) {
                $pdf->SetX($x + 1);
            }
            $pdf->Write(0, (string) $libero['number']);

            $pdf->SetXY($x + 5, $y);
            $pdf->Write(0, $this->formattedName($libero['last_name']));

            $y += 4;
        }
    }

    /**
     * @param  array<int, array{
     *     staff_key: int,
     *     role: StaffRole,
     *     last_name: string,
     *     first_name: string
     * }>  $staff
     */
    private function writeStaff(ScoresheetPdf $pdf, array $staff, int $x): void
    {
        $firstAssistantCoach = true;
        $pdf->SetFontSize(8);
        foreach ($staff as $staffMember) {
            $y = match ($staffMember['role']) {
                StaffRole::Coach => 248,
                StaffRole::AssistantCoach => $firstAssistantCoach ? 252 : 256,
                StaffRole::Therapist => 260,
                StaffRole::Doctor => 264,
            };
            $pdf->SetXY($x, $y);
            $pdf->Write(0, $this->formattedName($staffMember['last_name'], $staffMember['first_name']));
            if ($staffMember['role'] === StaffRole::AssistantCoach) {
                $firstAssistantCoach = false;
            }
        }
    }

    private function formattedName(string $lastName, ?string $firstName = null): string
    {
        $formattedLastName = str($lastName)->upper();
        $formattedFirstName = $firstName === null || $firstName === ''
            ? ''
            : str($firstName)->upper()->substr(0, 1)->toString();

        return $formattedFirstName === ''
            ? $formattedLastName->toString()
            : "{$formattedLastName}, {$formattedFirstName}.";
    }
}
