<?php

declare(strict_types=1);

use App\Core\Settings\Facades\Settings;
use App\Domains\Plans\Services\GoogleVisionOcrExtractor;
use App\Domains\Plans\Services\PdfPageIsolator;
use App\Domains\Plans\Services\SheetTextDetector;
use App\Domains\Plans\Services\Support\GhostscriptBinaryLocator;
use Illuminate\Support\Facades\Http;

it('throws when the Google Vision API key is not configured', function (): void {
    config(['services.google_vision.api_key' => '']);

    $extractor = new GoogleVisionOcrExtractor(new PdfPageIsolator, new SheetTextDetector);

    expect(fn () => $extractor->extract(base_path('tests/fixtures/sample-invoice.pdf'), 1))
        ->toThrow(RuntimeException::class, 'Google Vision API key is not configured. Set GOOGLE_VISION_API_KEY.');
});

it('extracts sheet metadata from a Google Vision response', function (): void {
    if (GhostscriptBinaryLocator::locate() === null) {
        $this->markTestSkipped('Ghostscript is not available in this environment.');
    }

    config(['services.google_vision.api_key' => 'test-key']);
    Settings::set('plans.sheet_number_pattern', '(?<![A-Z0-9])[A-Z]{1,3}[\\s.-]?\\d{1,3}(?:\\.\\d+)?(?![A-Z0-9])');

    Http::preventStrayRequests();
    Http::fake([
        'vision.googleapis.com/*' => Http::response([
            'responses' => [[
                'responses' => [[
                    'fullTextAnnotation' => ['text' => "SCHEDULES\nA 1.01"],
                ]],
            ]],
        ]),
    ]);

    $extractor = new GoogleVisionOcrExtractor(new PdfPageIsolator, new SheetTextDetector);
    $result = $extractor->extract(base_path('tests/fixtures/sample-invoice.pdf'), 1);

    expect($result['source'])->toBe('google-vision')
        ->and($result['sheet_number'])->toBe('A 1.01')
        ->and($result['text'])->toBe("SCHEDULES\nA 1.01");

    Http::assertSent(fn ($request): bool => str_contains($request->url(), 'files:annotate'));
});
