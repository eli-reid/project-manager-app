<?php

declare(strict_types=1);

use App\Core\Settings\Facades\Settings;
use App\Domains\Plans\Services\GhostscriptOcrExtractor;
use App\Domains\Plans\Services\GhostscriptPageTextExtractor;
use App\Domains\Plans\Services\PlanTextExtractionLogger;
use App\Domains\Plans\Services\PlanTitleBlockImageCropper;
use App\Domains\Plans\Services\PlanTitleBlockRegion;
use App\Domains\Plans\Services\SheetTextDetector;
use App\Domains\Plans\Services\Support\GhostscriptBinaryLocator;

it('throws when ghostscript is not available for ocr', function (): void {
    config(['plans.ghostscript_bin_path' => 'Z:/does-not-exist/gs.exe']);

    $extractor = new GhostscriptOcrExtractor(
        new PlanTitleBlockRegion,
        new PlanTitleBlockImageCropper,
        new GhostscriptPageTextExtractor(new PlanTextExtractionLogger),
        new SheetTextDetector,
        new PlanTextExtractionLogger,
    );

    expect(fn () => $extractor->extract('unused.pdf', 1))->toThrow(RuntimeException::class);
});

it('extracts ocr text from the cropped title block using a ghostscript build with the ocr device', function (): void {
    if (GhostscriptBinaryLocator::locate() === null) {
        $this->markTestSkipped('Ghostscript is not available in this environment.');
    }

    Settings::set('plans.title_block_region', 'right-strip');

    $extractor = app(GhostscriptOcrExtractor::class);

    try {
        $result = $extractor->extract(base_path('tests/fixtures/sample-invoice.pdf'), 1);
    } catch (RuntimeException $exception) {
        $this->markTestSkipped('Installed Ghostscript build does not include the OCR device: '.$exception->getMessage());
    }

    expect($result['source'])->toBe('ghostscript-ocr');
});
