<?php

declare(strict_types=1);

use App\Core\Settings\Facades\Settings;
use App\Domains\Plans\Services\GhostscriptOcrExtractor;
use App\Domains\Plans\Services\GoogleVisionOcrExtractor;
use App\Domains\Plans\Services\SheetMetadataExtractorManager;
use App\Domains\Plans\Services\TesseractOcrExtractor;

it('defaults to the ghostscript ocr driver', function (): void {
    Settings::set('plans.ocr_driver', 'ghostscript');

    $manager = app(SheetMetadataExtractorManager::class);

    expect($manager->getDefaultDriver())->toBe('ghostscript')
        ->and($manager->driver('ghostscript'))->toBeInstanceOf(GhostscriptOcrExtractor::class)
        ->and($manager->driver('google-vision'))->toBeInstanceOf(GoogleVisionOcrExtractor::class)
        ->and($manager->driver('tesseract'))->toBeInstanceOf(TesseractOcrExtractor::class);
});

it('uses google vision as the default driver when configured via settings', function (): void {
    Settings::set('plans.ocr_driver', 'google-vision');

    $manager = app(SheetMetadataExtractorManager::class);

    expect($manager->getDefaultDriver())->toBe('google-vision')
        ->and($manager->driver())->toBeInstanceOf(GoogleVisionOcrExtractor::class);
});
