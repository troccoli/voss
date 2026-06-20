<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

test('runtime code uses direct game queries only inside singleton access internals', function (): void {
    $allowedPaths = [
        app_path('Models/Game.php'),
        app_path('Services/CurrentMatchResolver.php'),
    ];

    $runtimePaths = collect(File::allFiles(app_path()))
        ->map(fn (SplFileInfo $file): string => $file->getPathname())
        ->reject(fn (string $path): bool => in_array($path, $allowedPaths, true))
        ->values();

    $violations = $runtimePaths
        ->filter(function (string $path): bool {
            $contents = File::get($path);

            return preg_match('/\bGame::(query|create|forceCreate|first|firstOrFail|find|findOrFail|sole|all|get|where|latest|oldest|count|exists)\s*\(/', $contents) === 1;
        })
        ->map(fn (string $path): string => str_replace(base_path().'/', '', $path))
        ->values()
        ->all();

    expect($violations)->toBe([]);
});
