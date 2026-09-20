<?php

declare(strict_types=1);

use App\Domains\Plans\Services\GhostscriptPageTextExtractor;
use App\Domains\Plans\Services\NullOcrExtractor;
use App\Domains\Plans\Services\PlanSheetTextResolver;
use App\Domains\Plans\Services\PlanTextExtractionLogger;
use App\Domains\Plans\Services\SheetTextDetector;
use Illuminate\Support\Facades\File;

beforeEach(function (): void {
    // Force Ghostscript to be considered unavailable regardless of the host machine.
    config(['plans.ghostscript_bin_path' => 'Z:/does-not-exist/gs.exe']);
    File::delete(storage_path('logs/plan-text-extraction.log'));
});

it('falls back to ocr when ghostscript is unavailable and fallback is enabled', function (): void {
    config(['plans.ocr_fallback_enabled' => true]);

    $resolver = new PlanSheetTextResolver(new GhostscriptPageTextExtractor(new PlanTextExtractionLogger), new NullOcrExtractor, new SheetTextDetector);

    $result = $resolver->resolve('unused.pdf', 1);

    expect($result['source'])->toBe('null');
});

it('rethrows the ghostscript error when ocr fallback is disabled', function (): void {
    config(['plans.ocr_fallback_enabled' => false]);

    $resolver = new PlanSheetTextResolver(new GhostscriptPageTextExtractor(new PlanTextExtractionLogger), new NullOcrExtractor, new SheetTextDetector);

    expect(fn () => $resolver->resolve('unused.pdf', 1))->toThrow(RuntimeException::class);
});

it('writes full ghostscript text extraction output to a dedicated log file', function (): void {
    $ghostscriptPath = storage_path('framework/testing/fake-gs-log.cmd');
    if (! is_dir(dirname($ghostscriptPath))) {
        mkdir(dirname($ghostscriptPath), 0755, true);
    }
    file_put_contents($ghostscriptPath, "@echo off\r\necho A-101 FLOOR PLAN\r\necho FULL OCR TEXT FROM IMAGE\r\n");

    config(['plans.ghostscript_bin_path' => $ghostscriptPath]);

    $text = (new GhostscriptPageTextExtractor(new PlanTextExtractionLogger))->extract('source-plan.pdf', 7);

    expect($text)->toContain('A-101 FLOOR PLAN')
        ->and(File::get(storage_path('logs/plan-text-extraction.log')))
        ->toContain('source: ghostscript-text-layer')
        ->toContain('file: source-plan.pdf')
        ->toContain('page: 7')
        ->toContain('A-101 FLOOR PLAN')
        ->toContain('FULL OCR TEXT FROM IMAGE');
});
