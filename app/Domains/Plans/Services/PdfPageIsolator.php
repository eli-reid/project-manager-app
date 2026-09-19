<?php

declare(strict_types=1);

namespace App\Domains\Plans\Services;

use App\Domains\Plans\Services\Support\GhostscriptBinaryLocator;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Process\Process;

/**
 * Splits a single page out of a plan set PDF into its own small PDF file.
 *
 * OCR drivers send this isolated page to the OCR provider instead of the
 * whole plan set, keeping upload payloads small and avoiding provider page
 * limits (Google Vision's synchronous endpoint accepts at most 5 pages per
 * request).
 */
final class PdfPageIsolator
{
    public function isolate(string $absolutePdfPath, int $page): string
    {
        $binary = GhostscriptBinaryLocator::locate();
        if ($binary === null) {
            throw new RuntimeException('Ghostscript is not available. Configure PLANS_GHOSTSCRIPT_BIN_PATH to the Ghostscript executable used by Imagick.');
        }

        $outputPath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'plan-page-'.Str::random(16).'.pdf';

        $process = new Process([
            $binary,
            '-q',
            '-dNOPAUSE',
            '-dBATCH',
            '-sDEVICE=pdfwrite',
            '-dFirstPage='.(string) $page,
            '-dLastPage='.(string) $page,
            '-sOutputFile='.$outputPath,
            $absolutePdfPath,
        ], null, null, null, (int) config('plans.process_timeout', 300));
        $process->mustRun();

        if (! is_file($outputPath)) {
            throw new RuntimeException("Ghostscript did not produce a single-page PDF for page {$page}.");
        }

        return $outputPath;
    }
}
