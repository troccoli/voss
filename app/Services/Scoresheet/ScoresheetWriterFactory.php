<?php

declare(strict_types=1);

namespace App\Services\Scoresheet;

use App\Services\Scoresheet\Contracts\ScoresheetSectionWriter;
use App\Services\Scoresheet\Writers\MatchInfoWriter;
use App\Services\Scoresheet\Writers\OfficialsWriter;
use App\Services\Scoresheet\Writers\TeamsWriter;
use InvalidArgumentException;

class ScoresheetWriterFactory
{
    public function make(string $type): ScoresheetSectionWriter
    {
        return match ($type) {
            'match_info' => app(MatchInfoWriter::class),
            'teams' => app(TeamsWriter::class),
            'officials' => app(OfficialsWriter::class),
            default => throw new InvalidArgumentException("Unknown writer type: {$type}"),
        };
    }
}
