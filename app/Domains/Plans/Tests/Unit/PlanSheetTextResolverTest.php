<?php

declare(strict_types=1);

use App\Domains\Plans\Services\GhostscriptPageTextExtractor;
use App\Domains\Plans\Services\NullOcrExtractor;
use App\Domains\Plans\Services\PlanSheetTextResolver;
use App\Domains\Plans\Services\SheetTextDetector;

beforeEach(function (): void {
    // Force Ghostscript to be considered unavailable regardless of the host machine.
    config(['plans.ghostscript_bin_path' => 'Z:/does-not-exist/gs.exe']);
});

it('falls back to ocr when ghostscript is unavailable and fallback is enabled', function (): void {
    config(['plans.ocr_fallback_enabled' => true]);

    $resolver = new PlanSheetTextResolver(new GhostscriptPageTextExtractor, new NullOcrExtractor, new SheetTextDetector);

    $result = $resolver->resolve('unused.pdf', 1);

    expect($result['source'])->toBe('null');
});

it('rethrows the ghostscript error when ocr fallback is disabled', function (): void {
    config(['plans.ocr_fallback_enabled' => false]);

    $resolver = new PlanSheetTextResolver(new GhostscriptPageTextExtractor, new NullOcrExtractor, new SheetTextDetector);

    expect(fn () => $resolver->resolve('unused.pdf', 1))->toThrow(RuntimeException::class);
});
