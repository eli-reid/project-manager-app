<?php

declare(strict_types=1);

namespace App\Domains\Plans\Services;

use App\Domains\Plans\Contracts\SheetMetadataExtractorContract;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * OCR driver backed by Google Cloud Vision.
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
        private readonly PlanTitleBlockRegion $titleBlockRegion,
        private readonly PlanTitleBlockImageCropper $titleBlockImageCropper,
    ) {}

    public function extract(string $absolutePdfPath, int $page): array
    {
        $apiKey = trim((string) config('services.google_vision.api_key', ''));
        if ($apiKey === '') {
            throw new RuntimeException('Google Vision API key is not configured. Set GOOGLE_VISION_API_KEY.');
        }

        $input = $this->buildVisionInput($absolutePdfPath, $page);

        try {
            $response = Http::timeout((int) config('services.google_vision.timeout', 30))
                ->connectTimeout((int) config('services.google_vision.connect_timeout', 5))
                ->retry(2, 250, throw: false)
                ->post($input['endpoint'].'?key='.$apiKey, [
                    'requests' => [$input['request']],
                ])
                ->throw();
        } finally {
            File::delete($input['path']);
        }

        $result = (array) data_get($response->json(), $input['result_path'], []);

        $error = data_get($result, 'error.message');
        if ($error !== null) {
            throw new RuntimeException("Google Vision OCR failed: {$error}");
        }

        $text = (string) data_get($result, 'fullTextAnnotation.text', '');
        $this->extractionLogger->record($input['source'], $absolutePdfPath, $page, $text);

        $detection = $this->detector->detect($text);

        return [
            'sheet_number' => $detection['sheet_number'],
            'title' => $detection['title'],
            'confidence' => $detection['confidence'],
            'source' => 'google-vision',
            'text' => $text,
        ];
    }

    /**
     * @return array{endpoint:string,path:string,request:array<string,mixed>,result_path:string,source:string}
     */
    private function buildVisionInput(string $absolutePdfPath, int $page): array
    {
        $region = $this->titleBlockRegion->resolve();

        if ($region !== null) {
            $croppedImagePath = $this->titleBlockImageCropper->crop($absolutePdfPath, $page, $region);

            return [
                'endpoint' => (string) config('services.google_vision.image_endpoint'),
                'path' => $croppedImagePath,
                'request' => [
                    'image' => ['content' => base64_encode(File::get($croppedImagePath))],
                    'features' => [['type' => 'DOCUMENT_TEXT_DETECTION']],
                ],
                'result_path' => 'responses.0',
                'source' => 'google-vision-title-block',
            ];
        }

        $singlePagePath = $this->pageIsolator->isolate($absolutePdfPath, $page);

        return [
            'endpoint' => (string) config('services.google_vision.endpoint'),
            'path' => $singlePagePath,
            'request' => [
                'inputConfig' => [
                    'content' => base64_encode(File::get($singlePagePath)),
                    'mimeType' => 'application/pdf',
                ],
                'features' => [['type' => 'DOCUMENT_TEXT_DETECTION']],
                'pages' => [1],
            ],
            'result_path' => 'responses.0.responses.0',
            'source' => 'google-vision-ocr',
        ];
    }
}
