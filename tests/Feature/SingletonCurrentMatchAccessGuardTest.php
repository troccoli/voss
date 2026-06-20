<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

test('singleton match livewire components do not use direct static Game access', function (): void {
    // Historical migrations may still mention championship-era schema names; that is preserved
    // migration history, not the active single-match runtime architecture this guard enforces.
    $componentPaths = collect(File::files(app_path('Livewire')))
        ->map(fn (SplFileInfo $file): string => $file->getPathname())
        ->reject(fn (string $path): bool => str_ends_with($path, 'MatchSetup.php'))
        ->values();

    $violations = $componentPaths
        ->filter(function (string $path): bool {
            $contents = File::get($path);

            return preg_match('/\bGame::(?!current\b)[A-Za-z_][A-Za-z0-9_]*\s*\(/', $contents) === 1;
        })
        ->map(fn (string $path): string => str_replace(base_path().'/', '', $path))
        ->values()
        ->all();

    expect($violations)->toBe([]);
});
