<?php

declare(strict_types=1);

use App\Services\Scoresheet\ScoresheetPdf;
use App\Services\Scoresheet\ScoresheetPdfFactory;

class RecordingScoresheetPdf extends ScoresheetPdf
{
    /** @var array<int, array{x: float, y: float, text: string}> */
    public array $writes = [];

    /** @var array<int, array{x: int, y: int, text: string}> */
    public array $spacedPrints = [];

    /** @var array<int, array{x: float, y: float, r: float, line_width: ?float}> */
    public array $circles = [];

    /** @var array<int, array{x1: float, y1: float, x2: float, y2: float}> */
    public array $lines = [];

    /** @var array<int, string> */
    public array $sourceFiles = [];

    /** @var array<int, string> */
    public array $outputPaths = [];

    private float $cursorX = 0;

    private float $cursorY = 0;

    public function __construct() {}

    #[Override]
    public function setSourceFile($file)
    {
        $this->sourceFiles[] = (string) $file;

        return 1;
    }

    #[Override]
    public function importPage($pageNumber, $box = '/CropBox', $groupXObject = true, $importExternalLinks = false)
    {
        return 1;
    }

    #[Override]
    public function AddPage($orientation = '', $size = '', $rotation = 0): void {}

    #[Override]
    public function useTemplate($tpl, $x = null, $y = null, $width = 0, $height = 0, $adjustPageSize = false): array
    {
        return [];
    }

    #[Override]
    public function SetMargins($left, $top, $right = -1): void {}

    #[Override]
    public function SetAutoPageBreak($auto, $margin = 0): void {}

    #[Override]
    public function SetDisplayMode($zoom, $layout = 'continuous'): void {}

    #[Override]
    public function SetFont($family, $style = '', $size = 0): void {}

    #[Override]
    public function SetXY($x, $y): void
    {
        $this->cursorX = (float) $x;
        $this->cursorY = (float) $y;
    }

    #[Override]
    public function SetX($x): void
    {
        $this->cursorX = (float) $x;
    }

    #[Override]
    public function Write(mixed $h, mixed $txt, mixed $link = ''): void
    {
        $this->writes[] = [
            'x' => $this->cursorX,
            'y' => $this->cursorY,
            'text' => (string) $txt,
        ];
    }

    #[Override]
    public function spacedPrint(int $x, int $y, string $text): void
    {
        $this->spacedPrints[] = [
            'x' => $x,
            'y' => $y,
            'text' => $text,
        ];
    }

    #[Override]
    public function circle(float $x, float $y, float $r, ?float $lineWidth = null): void
    {
        $this->circles[] = [
            'x' => $x,
            'y' => $y,
            'r' => $r,
            'line_width' => $lineWidth,
        ];
    }

    #[Override]
    public function Line($x1, $y1, $x2, $y2): void
    {
        $this->lines[] = [
            'x1' => (float) $x1,
            'y1' => (float) $y1,
            'x2' => (float) $x2,
            'y2' => (float) $y2,
        ];
    }

    #[Override]
    public function SetFontSize($size): void {}

    #[Override]
    public function SetLineWidth($width): void {}

    #[Override]
    public function Output($dest = '', $name = '', $isUTF8 = false): string
    {
        if ($dest === 'F' && is_string($name) && $name !== '') {
            file_put_contents($name, 'fake pdf output');
            $this->outputPaths[] = $name;
        }

        return 'fake pdf output';
    }
}

class FakeScoresheetPdfFactory extends ScoresheetPdfFactory
{
    public function __construct(
        private readonly RecordingScoresheetPdf $pdf
    ) {}

    #[Override]
    public function make(): ScoresheetPdf
    {
        return $this->pdf;
    }
}

function fakeScoresheetPdfFactory(): RecordingScoresheetPdf
{
    $pdf = new RecordingScoresheetPdf;

    app()->instance(ScoresheetPdfFactory::class, new FakeScoresheetPdfFactory($pdf));

    return $pdf;
}
