<?php

declare(strict_types=1);

namespace App\Domains\Plans\Services;

use App\Core\Settings\Facades\Settings;
use Illuminate\Support\Manager;

final class SheetMetadataExtractorManager extends Manager
{
    public function createGoogleVisionDriver(): GoogleVisionOcrExtractor
    {
        return $this->container->make(GoogleVisionOcrExtractor::class);
    }

    public function createTesseractDriver(): TesseractOcrExtractor
    {
        return $this->container->make(TesseractOcrExtractor::class);
    }

    public function createGhostscriptDriver(): GhostscriptOcrExtractor
    {
        return $this->container->make(GhostscriptOcrExtractor::class);
    }

    public function getDefaultDriver(): string
    {
        return Settings::get('plans.ocr_driver', config('plans.ocr_driver', 'ghostscript'))->toString();
    }
}
