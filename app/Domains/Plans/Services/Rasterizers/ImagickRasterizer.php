<?php

declare(strict_types=1);

namespace App\Domains\Plans\Services\Rasterizers;

use App\Domains\Plans\Contracts\PlanRasterizerContract;
use RuntimeException;

final class ImagickRasterizer implements PlanRasterizerContract
{
    public function pageCount(string $absolutePdfPath): int
    {
        $imagick = $this->newImagick();
        $imagick->pingImage($absolutePdfPath);

        return $imagick->getNumberImages();
    }

    public function renderPage(
        string $absolutePdfPath,
        int $page,
        int $dpi,
        string $absoluteOutPath,
    ): void {
        $imagick = $this->newImagick();
        $imagick->setResolution($dpi, $dpi);
        $imagick->readImage($absolutePdfPath.'['.($page - 1).']');
        $imagick->setImageBackgroundColor('white');
        $imagick->setImageAlphaChannel(\Imagick::ALPHACHANNEL_REMOVE);
        $imagick->setImageFormat(pathinfo($absoluteOutPath, PATHINFO_EXTENSION) ?: 'png');
        $imagick->writeImage($absoluteOutPath);
        $imagick->clear();
        $imagick->destroy();
    }

    public function isAvailable(): bool
    {
        if (! extension_loaded('imagick')) {
            return false;
        }

        try {
            $imagick = new \Imagick;
            $imagick->setIteratorIndex(0);
            $imagick->pingImage('logo:');

            return in_array('PDF', array_map('strtoupper', $imagick->queryFormats('PDF')), true);
        } catch (\Throwable) {
            return false;
        }
    }

    public function name(): string
    {
        return 'imagick';
    }

    private function newImagick(): \Imagick
    {
        if (! $this->isAvailable()) {
            throw new RuntimeException('Imagick or its PDF delegate is not available.');
        }

        return new \Imagick;
    }
}
