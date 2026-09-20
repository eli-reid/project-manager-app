<?php

declare(strict_types=1);

namespace App\Domains\Plans\Services;

use App\Domains\Plans\Services\Support\GhostscriptBinaryLocator;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Process\Process;

final class PlanTitleBlockImageCropper
{
    /**
     * @param  array{x:float,y:float,width:float,height:float}  $region
     */
    public function crop(string $absolutePdfPath, int $page, array $region): string
    {
        if (GhostscriptBinaryLocator::locate() !== null && function_exists('imagecreatefrompng')) {
            return $this->cropWithGhostscript($absolutePdfPath, $page, $region);
        }

        return $this->cropWithImagick($absolutePdfPath, $page, $region);
    }

    /**
     * @param  array{x:float,y:float,width:float,height:float}  $region
     */
    private function cropWithGhostscript(string $absolutePdfPath, int $page, array $region): string
    {
        $binary = GhostscriptBinaryLocator::locate();
        if ($binary === null) {
            throw new RuntimeException('Ghostscript is not available. Configure PLANS_GHOSTSCRIPT_BIN_PATH to crop the title block before OCR.');
        }

        $renderedPath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'plan-title-block-source-'.Str::random(16).'.png';
        $outputPath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'plan-title-block-'.Str::random(16).'.png';

        try {
            $process = new Process([
                $binary,
                '-q',
                '-dNOPAUSE',
                '-dBATCH',
                '-sDEVICE=png16m',
                '-r'.(string) config('plans.render_dpi', 150),
                '-dFirstPage='.(string) $page,
                '-dLastPage='.(string) $page,
                '-sOutputFile='.$renderedPath,
                $absolutePdfPath,
            ], null, null, null, (int) config('plans.process_timeout', 300));
            $process->mustRun();

            if (! is_file($renderedPath)) {
                throw new RuntimeException('Ghostscript did not render the title block source image.');
            }

            $source = imagecreatefrompng($renderedPath);
            if ($source === false) {
                throw new RuntimeException('Unable to read Ghostscript title block source image.');
            }

            $imageWidth = imagesx($source);
            $imageHeight = imagesy($source);
            $crop = imagecrop($source, $this->pixelRegion($region, $imageWidth, $imageHeight));
            imagedestroy($source);

            if ($crop === false) {
                throw new RuntimeException('Unable to crop the Ghostscript title block image.');
            }

            imagepng($crop, $outputPath);
            imagedestroy($crop);
        } finally {
            if (is_file($renderedPath)) {
                unlink($renderedPath);
            }
        }

        if (! is_file($outputPath)) {
            throw new RuntimeException('Unable to crop the title block image for OCR.');
        }

        return $outputPath;
    }

    /**
     * @param  array{x:float,y:float,width:float,height:float}  $region
     */
    private function cropWithImagick(string $absolutePdfPath, int $page, array $region): string
    {
        if (! extension_loaded('imagick')) {
            throw new RuntimeException('Ghostscript with GD or Imagick is required to crop the title block before OCR.');
        }

        $outputPath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'plan-title-block-'.Str::random(16).'.png';
        $imagickClass = 'Imagick';
        $imagick = new $imagickClass;

        try {
            $dpi = (int) config('plans.render_dpi', 150);
            $imagick->setResolution($dpi, $dpi);
            $imagick->readImage($absolutePdfPath.'['.($page - 1).']');
            $imagick->setImageBackgroundColor('white');
            $imagick->setImageAlphaChannel(constant('Imagick::ALPHACHANNEL_REMOVE'));

            $imageWidth = $imagick->getImageWidth();
            $imageHeight = $imagick->getImageHeight();
            $pixelRegion = $this->pixelRegion($region, $imageWidth, $imageHeight);

            $imagick->cropImage(
                $pixelRegion['width'],
                $pixelRegion['height'],
                $pixelRegion['x'],
                $pixelRegion['y'],
            );
            $imagick->setImagePage(0, 0, 0, 0);
            $imagick->setImageFormat('png');
            $imagick->writeImage($outputPath);
        } finally {
            $imagick->clear();
            $imagick->destroy();
        }

        if (! is_file($outputPath)) {
            throw new RuntimeException('Unable to crop the title block image for OCR.');
        }

        return $outputPath;
    }

    /**
     * @param  array{x:float,y:float,width:float,height:float}  $region
     * @return array{x:int,y:int,width:int,height:int}
     */
    private function pixelRegion(array $region, int $imageWidth, int $imageHeight): array
    {
        $cropX = (int) round($imageWidth * $region['x']);
        $cropY = (int) round($imageHeight * $region['y']);
        $cropX = min($cropX, $imageWidth - 1);
        $cropY = min($cropY, $imageHeight - 1);
        $cropWidth = max(1, (int) round($imageWidth * $region['width']));
        $cropHeight = max(1, (int) round($imageHeight * $region['height']));

        return [
            'x' => $cropX,
            'y' => $cropY,
            'width' => min($cropWidth, $imageWidth - $cropX),
            'height' => min($cropHeight, $imageHeight - $cropY),
        ];
    }
}
