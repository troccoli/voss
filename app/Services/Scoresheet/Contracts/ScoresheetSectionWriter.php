<?php

namespace App\Services\Scoresheet\Contracts;

use App\Models\Game;
use App\Services\Scoresheet\ScoresheetPdf;

interface ScoresheetSectionWriter
{
    public function write(ScoresheetPdf $pdf, Game $game): void;
}
