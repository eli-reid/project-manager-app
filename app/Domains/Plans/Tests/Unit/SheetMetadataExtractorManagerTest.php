<?php

declare(strict_types=1);

use App\Domains\Plans\Services\GhostscriptOcrExtractor;
use App\Domains\Plans\Services\GoogleVisionOcrExtractor;
use App\Domains\Plans\Services\SheetMetadataExtractorManager;
use App\Domains\Plans\Services\TesseractOcrExtractor;

it('defaults to the ghostscript ocr driver', function (): void {
    config(['plans.ocr_driver' => 'ghostscript']);

    $manager = app(SheetMetadataExtractorManager::class);

    expect($manager->getDefaultDriver())->toBe('ghostscript')
        ->and($manager->driver('ghostscript'))->toBeInstanceOf(GhostscriptOcrExtractor::class)
        ->and($manager->driver('google-vision'))->toBeInstanceOf(GoogleVisionOcrExtractor::class)
        ->and($manager->driver('tesseract'))->toBeInstanceOf(TesseractOcrExtractor::class);
});
