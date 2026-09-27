<?php

declare(strict_types=1);

namespace App\Domains\Plans\Services;

use RuntimeException;

/**
 * Downsizes a rendered page image into a small grid thumbnail so the Plans tab
 * never ships the full render (often several MB per sheet) to list/grid views.
 */
final class PlanThumbnailGenerator
{
    public function generate(string $absoluteSourcePath, string $absoluteDestinationPath, int $width): void
    {
        $source = imagecreatefrompng($absoluteSourcePath);

        if ($source === false) {
            throw new RuntimeException("Unable to read rendered page image [{$absoluteSourcePath}].");
        }

        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);
        $targetWidth = min($width, $sourceWidth);
        $targetHeight = (int) max(1, round($sourceHeight * ($targetWidth / $sourceWidth)));

        $thumbnail = imagecreatetruecolor($targetWidth, $targetHeight);
        imagefill($thumbnail, 0, 0, (int) imagecolorallocate($thumbnail, 255, 255, 255));
        imagecopyresampled($thumbnail, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $sourceWidth, $sourceHeight);
        imagedestroy($source);

        $directory = dirname($absoluteDestinationPath);

        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw new RuntimeException("Unable to create thumbnail directory [{$directory}].");
        }

        $extension = strtolower((string) pathinfo($absoluteDestinationPath, PATHINFO_EXTENSION));
        $written = $extension === 'webp' && function_exists('imagewebp')
            ? imagewebp($thumbnail, $absoluteDestinationPath, 82)
            : imagepng($thumbnail, $absoluteDestinationPath);

        imagedestroy($thumbnail);

        if ($written === false) {
            throw new RuntimeException("Unable to write thumbnail image [{$absoluteDestinationPath}].");
        }
    }
}
