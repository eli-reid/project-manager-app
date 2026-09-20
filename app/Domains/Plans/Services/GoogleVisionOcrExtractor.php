<?php

declare(strict_types=1);

namespace App\Domains\Plans\Services;

use App\Domains\Plans\Contracts\SheetMetadataExtractorContract;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * OCR driver backed by Google Cloud Vision's `files:annotate` endpoint.
 *
 * Rather than rasterizing the page to an image locally and running a local
 * OCR binary, this driver isolates the single target page as its own small
 * PDF (see PdfPageIsolator) and ships that PDF straight to Vision. Vision
 * accepts PDF input natively and performs the page-to-image conversion on
 * Google's infrastructure, which keeps this server lightweight and avoids a
 * second local rasterization pass.
 *
 * No custom training set is required: DOCUMENT_TEXT_DETECTION is a
 * general-purpose pretrained model. Accuracy on title blocks should still be
 * spot-checked against a representative sample of real sheets (small fonts,
 * rotated text, and low-contrast scans are the most common failure modes);
 * if accuracy is inconsistent, a custom Document AI processor is the next
 * step up, not additional Vision training.
 */
final class GoogleVisionOcrExtractor implements SheetMetadataExtractorContract
{
    public function __construct(
        private readonly PdfPageIsolator $pageIsolator,
        private readonly SheetTextDetector $detector,
        private readonly PlanTextExtractionLogger $extractionLogger,
    ) {}

    public function extract(string $absolutePdfPath, int $page): array
    {
        $apiKey = trim((string) config('services.google_vision.api_key', ''));
        if ($apiKey === '') {
            throw new RuntimeException('Google Vision API key is not configured. Set GOOGLE_VISION_API_KEY.');
        }

        $singlePagePath = $this->pageIsolator->isolate($absolutePdfPath, $page);

        try {
            $response = Http::timeout((int) config('services.google_vision.timeout', 30))
                ->connectTimeout((int) config('services.google_vision.connect_timeout', 5))
                ->retry(2, 250, throw: false)
                ->post(config('services.google_vision.endpoint').'?key='.$apiKey, [
                    'requests' => [[
                        'inputConfig' => [
                            'content' => base64_encode(File::get($singlePagePath)),
                            'mimeType' => 'application/pdf',
                        ],
                        'features' => [['type' => 'DOCUMENT_TEXT_DETECTION']],
                        'pages' => [1],
                    ]],
                ])
                ->throw();
        } finally {
            File::delete($singlePagePath);
        }

        $result = (array) data_get($response->json(), 'responses.0.responses.0', []);

        $error = data_get($result, 'error.message');
        if ($error !== null) {
            throw new RuntimeException("Google Vision OCR failed: {$error}");
        }

        $text = (string) data_get($result, 'fullTextAnnotation.text', '');
        $this->extractionLogger->record('google-vision-ocr', $absolutePdfPath, $page, $text);

        $detection = $this->detector->detect($text);

        return [
            'sheet_number' => $detection['sheet_number'],
            'title' => $detection['title'],
            'confidence' => $detection['confidence'],
            'source' => 'google-vision',
            'text' => $text,
        ];
    }
}
