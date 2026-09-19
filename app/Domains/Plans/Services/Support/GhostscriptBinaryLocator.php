<?php

declare(strict_types=1);

namespace App\Domains\Plans\Services\Support;

use Symfony\Component\Process\ExecutableFinder;

/**
 * Resolves the Ghostscript executable shared by the text-layer extractor and
 * the single-page PDF isolator used ahead of OCR.
 */
final class GhostscriptBinaryLocator
{
    public static function locate(): ?string
    {
        $configuredPath = trim((string) config('plans.ghostscript_bin_path', ''));
        if ($configuredPath !== '') {
            return is_file($configuredPath) ? $configuredPath : null;
        }

        $finder = new ExecutableFinder;

        return $finder->find(PHP_OS_FAMILY === 'Windows' ? 'gswin64c' : 'gs');
    }
}
