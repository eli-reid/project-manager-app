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
    public function generate(string $absoluteSourcePath, string $absoluteDestinationPath, int $width, int $quality = 60, int $maxBytes = 204800): void
    {
        $source = imagecreatefrompng($absoluteSourcePath);

        if ($source === false) {
            throw new RuntimeException("Unable to read rendered page image [{$absoluteSourcePath}].");
        }

        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);
        $targetWidth = min(max(1, $width), $sourceWidth);

        $directory = dirname($absoluteDestinationPath);

        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw new RuntimeException("Unable to create thumbnail directory [{$directory}].");
        }

        $extension = strtolower((string) pathinfo($absoluteDestinationPath, PATHINFO_EXTENSION));
        $quality = max(0, min(100, $quality));
        $maxBytes = max(1, $maxBytes);
        $minimumWidth = min($targetWidth, 64);

        while ($targetWidth >= $minimumWidth) {
            $targetHeight = (int) max(1, round($sourceHeight * ($targetWidth / $sourceWidth)));
            $thumbnail = imagecreatetruecolor($targetWidth, $targetHeight);
            imagefill($thumbnail, 0, 0, (int) imagecolorallocate($thumbnail, 255, 255, 255));
            imagecopyresampled($thumbnail, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $sourceWidth, $sourceHeight);

            $written = $extension === 'webp' && function_exists('imagewebp')
                ? imagewebp($thumbnail, $absoluteDestinationPath, $quality)
                : imagepng($thumbnail, $absoluteDestinationPath, 9);

            imagedestroy($thumbnail);

            if ($written === false) {
                imagedestroy($source);
                throw new RuntimeException("Unable to write thumbnail image [{$absoluteDestinationPath}].");
            }

            $fileSize = filesize($absoluteDestinationPath);

            if ($fileSize !== false && $fileSize <= $maxBytes) {
                imagedestroy($source);

                return;
            }

            if ($extension === 'webp' && function_exists('imagewebp') && $quality > 20) {
                $quality = max(20, $quality - 10);

                continue;
            }

            $nextWidth = (int) floor($targetWidth * 0.85);

            if ($nextWidth === $targetWidth) {
                break;
            }

            $targetWidth = $nextWidth;
        }

        imagedestroy($source);
        throw new RuntimeException("Unable to reduce thumbnail below {$maxBytes} bytes [{$absoluteDestinationPath}].");
    }
}
