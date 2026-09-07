<?php

declare(strict_types=1);

namespace App\Domains\Plans\Services\Rasterizers;

use App\Domains\Plans\Contracts\PlanRasterizerContract;
use RuntimeException;

final class NullRasterizer implements PlanRasterizerContract
{
    private const PLACEHOLDER_PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=';

    public function pageCount(string $absolutePdfPath): int
    {
        return (int) config('plans.null_page_count', 1);
    }

    public function renderPage(
        string $absolutePdfPath,
        int $page,
        int $dpi,
        string $absoluteOutPath,
    ): void {
        $directory = dirname($absoluteOutPath);

        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw new RuntimeException("Unable to create rasterizer output directory [{$directory}].");
        }

        $bytes = base64_decode(self::PLACEHOLDER_PNG, true);

        if ($bytes === false || file_put_contents($absoluteOutPath, $bytes) === false) {
            throw new RuntimeException("Unable to write rasterizer output [{$absoluteOutPath}].");
        }
    }

    public function isAvailable(): bool
    {
        return true;
    }

    public function name(): string
    {
        return 'null';
    }
}
