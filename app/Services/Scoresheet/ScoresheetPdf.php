<?php

declare(strict_types=1);

namespace App\Services\Scoresheet;

use setasign\Fpdi\Fpdi;

class ScoresheetPdf extends Fpdi
{
    public function circle(float $x, float $y, float $r, ?float $lineWidth = null): void
    {
        $previousLineWidth = $this->LineWidth;

        if ($lineWidth !== null) {
            $this->SetLineWidth($lineWidth);
        }

        $lx = 4 / 3 * (M_SQRT2 - 1) * $r;
        $k = $this->k;
        $h = $this->h;

        $this->_out(sprintf('%.2F %.2F m', ($x + $r) * $k, ($h - $y) * $k));
        $this->_out(sprintf('%.2F %.2F %.2F %.2F %.2F %.2F c',
            ($x + $r) * $k, ($h - ($y - $lx)) * $k,
            ($x + $lx) * $k, ($h - ($y - $r)) * $k,
            $x * $k, ($h - ($y - $r)) * $k));
        $this->_out(sprintf('%.2F %.2F %.2F %.2F %.2F %.2F c',
            ($x - $lx) * $k, ($h - ($y - $r)) * $k,
            ($x - $r) * $k, ($h - ($y - $lx)) * $k,
            ($x - $r) * $k, ($h - $y) * $k));
        $this->_out(sprintf('%.2F %.2F %.2F %.2F %.2F %.2F c',
            ($x - $r) * $k, ($h - ($y + $lx)) * $k,
            ($x - $lx) * $k, ($h - ($y + $r)) * $k,
            $x * $k, ($h - ($y + $r)) * $k));
        $this->_out(sprintf('%.2F %.2F %.2F %.2F %.2F %.2F c S',
            ($x + $lx) * $k, ($h - ($y + $r)) * $k,
            ($x + $r) * $k, ($h - ($y + $lx)) * $k,
            ($x + $r) * $k, ($h - $y) * $k));

        $this->SetLineWidth($previousLineWidth);
    }

    /** @phpstan-ignore-next-line  */
    public function Write($h, $txt, $link = ''): void
    {
        parent::Write($h, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', (string) $txt), $link);
    }

    public function spacedPrint(int $x, int $y, string $text): void
    {
        $distances = [6, 6, 6, 5];
        $this->SetXY($x, $y);
        foreach (mb_str_split($text) as $i => $character) {
            if ($character != ' ') {
                if (in_array($character, ['i', 'l', 'G', 'I'])) {
                    $this->SetXY($x + 1, $y);
                }
                $this->Write(0, $character);
            }
            $x = $x + $distances[$i % count($distances)];
            $this->SetXY($x, $y);
        }
    }
}
