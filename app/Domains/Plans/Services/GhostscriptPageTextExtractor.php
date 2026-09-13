<?php

declare(strict_types=1);

namespace App\Domains\Plans\Services;

use RuntimeException;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

final class GhostscriptPageTextExtractor
{
    public function extract(string $absolutePdfPath, int $page): string
    {
        $binary = $this->binaryPath();
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

    private function binaryPath(): ?string
    {
        $configuredPath = trim((string) config('plans.ghostscript_bin_path', ''));
        if ($configuredPath !== '') {
            return is_file($configuredPath) ? $configuredPath : null;
        }

        $finder = new ExecutableFinder;

        return $finder->find(PHP_OS_FAMILY === 'Windows' ? 'gswin64c' : 'gs');
    }
}
