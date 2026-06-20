<?php

declare(strict_types=1);

namespace App\Enums;

enum MatchPhase: string
{
    case Setup = 'setup';
    case Ready = 'ready';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case PdfGenerated = 'pdf_generated';

    public function allowsSetupEdits(): bool
    {
        return match ($this) {
            self::Setup, self::Ready => true,
            self::InProgress, self::Completed, self::PdfGenerated => false,
        };
    }

    public function allowsGameplayRecording(): bool
    {
        return match ($this) {
            self::Ready, self::InProgress => true,
            self::Setup, self::Completed, self::PdfGenerated => false,
        };
    }

    public function allowsPdfGeneration(): bool
    {
        return match ($this) {
            self::Completed, self::PdfGenerated => true,
            self::Setup, self::Ready, self::InProgress => false,
        };
    }
}
