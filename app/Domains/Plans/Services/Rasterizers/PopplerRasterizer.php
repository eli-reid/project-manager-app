<?php

declare(strict_types=1);

namespace App\Domains\Plans\Services\Rasterizers;

use App\Domains\Plans\Contracts\PlanRasterizerContract;
use RuntimeException;
use Symfony\Component\Process\Process;

final class PopplerRasterizer implements PlanRasterizerContract
{
    public function pageCount(string $absolutePdfPath): int
    {
        $process = $this->process('pdfinfo', [$absolutePdfPath]);
        $process->mustRun();

        if (preg_match('/^Pages:\s+(\d+)$/mi', $process->getOutput(), $matches) !== 1) {
            throw new RuntimeException('Poppler did not report a page count.');
        }

        return (int) $matches[1];
    }

    public function renderPage(
        string $absolutePdfPath,
        int $page,
        int $dpi,
        string $absoluteOutPath,
    ): void {
        $prefix = preg_replace('/\.png$/i', '', $absoluteOutPath) ?: $absoluteOutPath;
        $process = $this->process('pdftoppm', [
            '-r', (string) $dpi,
            '-f', (string) $page,
            '-l', (string) $page,
            '-png',
            '-singlefile',
            $absolutePdfPath,
            $prefix,
        ]);
        $process->mustRun();
    }

    public function isAvailable(): bool
    {
        return $this->binaryPath('pdfinfo') !== null
            && $this->binaryPath('pdftoppm') !== null;
    }

    public function name(): string
    {
        return 'poppler';
    }

    private function process(string $binary, array $arguments): Process
    {
        $path = $this->binaryPath($binary);

        if ($path === null) {
            throw new RuntimeException("Poppler binary [{$binary}] is not available.");
        }

        return new Process([$path, ...$arguments], null, null, null, (int) config('plans.process_timeout', 300));
    }

    private function binaryPath(string $binary): ?string
    {
        $directory = trim((string) config('plans.poppler_bin_path', ''));
        $executable = $binary.(PHP_OS_FAMILY === 'Windows' ? '.exe' : '');

        if ($directory !== '') {
            $candidate = rtrim($directory, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$executable;

            return is_file($candidate) ? $candidate : null;
        }

        return $this->findOnPath($executable);
    }

    private function findOnPath(string $executable): ?string
    {
        $path = getenv('PATH');

        if (! is_string($path)) {
            return null;
        }

        foreach (explode(PATH_SEPARATOR, $path) as $directory) {
            $candidate = rtrim($directory, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$executable;

            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }
}
