<?php

declare(strict_types=1);

use App\Domains\Plans\Services\GoogleVisionOcrExtractor;
use App\Domains\Plans\Services\SheetMetadataExtractorManager;
use App\Domains\Plans\Services\TesseractOcrExtractor;

it('defaults to the google-vision ocr driver', function (): void {
    config(['plans.ocr_driver' => 'google-vision']);

    $manager = app(SheetMetadataExtractorManager::class);

    expect($manager->getDefaultDriver())->toBe('google-vision')
        ->and($manager->driver('google-vision'))->toBeInstanceOf(GoogleVisionOcrExtractor::class)
        ->and($manager->driver('tesseract'))->toBeInstanceOf(TesseractOcrExtractor::class);
});
