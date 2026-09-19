<?php

declare(strict_types=1);

namespace App\Domains\Plans\Services;

use App\Domains\Plans\Services\Support\GhostscriptBinaryLocator;
use RuntimeException;
use Symfony\Component\Process\Process;

final class GhostscriptPageTextExtractor
{
    public function extract(string $absolutePdfPath, int $page): string
    {
        $binary = GhostscriptBinaryLocator::locate();
        if ($binary === null) {
            throw new RuntimeException('Ghostscript is not available. Configure PLANS_GHOSTSCRIPT_BIN_PATH to the Ghostscript executable used by Imagick.');
        }

        $process = new Process([
            $binary,
            '-q',
            '-dNOPAUSE',
            '-dBATCH',
            '-sDEVICE=txtwrite',
            '-dFirstPage='.(string) $page,
            '-dLastPage='.(string) $page,
            '-sOutputFile=-',
            $absolutePdfPath,
        ], null, null, null, (int) config('plans.process_timeout', 300));
        $process->mustRun();

        return trim($process->getOutput());
    }
}
