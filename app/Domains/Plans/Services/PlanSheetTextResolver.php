<?php

declare(strict_types=1);

namespace App\Domains\Plans\Services;

use App\Core\Settings\Facades\Settings;
use App\Domains\Plans\Contracts\SheetMetadataExtractorContract;
use RuntimeException;

/**
 * Resolves sheet metadata for a page, preferring the fast, free PDF text
 * layer and falling back to OCR only when the text layer looks empty (e.g. a
 * scanned drawing with no embedded text) and OCR fallback is enabled via
 * `plans.ocr_fallback_enabled`. OCR fallback is opt-in because it sends page
 * content to an external provider and incurs per-page cost.
 */
final class PlanSheetTextResolver
{
    public function __construct(
        private readonly GhostscriptPageTextExtractor $textExtractor,
        private readonly SheetMetadataExtractorContract $ocrExtractor,
        private readonly SheetTextDetector $detector,
        private readonly PlanTitleBlockRegion $titleBlockRegion,
    ) {}

    /**
     * @return array{sheet_number:?string,title:?string,confidence:float,source:string,text:string}
     */
    public function resolve(string $absolutePdfPath, int $page): array
    {
        $text = '';
        $ghostscriptError = null;
        $focusedOcrError = null;

        if ($this->shouldPreferFocusedOcr()) {
            try {
                return $this->ocrExtractor->extract($absolutePdfPath, $page);
            } catch (RuntimeException $exception) {
                $focusedOcrError = $exception;
            }
        }

        try {
            $text = $this->textExtractor->extract($absolutePdfPath, $page);
        } catch (RuntimeException $exception) {
            $ghostscriptError = $exception;
        }

        if ($this->needsOcrFallback($text)) {
            if ($focusedOcrError !== null) {
                throw $focusedOcrError;
            }

            return $this->ocrExtractor->extract($absolutePdfPath, $page);
        }

        if ($ghostscriptError !== null) {
            throw $ghostscriptError;
        }

        $detection = $this->detector->detect($text);

        return [
            'sheet_number' => $detection['sheet_number'],
            'title' => $detection['title'],
            'confidence' => $detection['confidence'],
            'source' => 'text-layer',
            'text' => $text,
        ];
    }

    private function needsOcrFallback(string $text): bool
    {
        return $this->ocrFallbackEnabled()
            && mb_strlen(trim($text)) < Settings::get('plans.ocr_min_text_length', config('plans.ocr_min_text_length', 12))->toInt();
    }

    private function shouldPreferFocusedOcr(): bool
    {
        return $this->ocrFallbackEnabled()
            && $this->titleBlockRegion->resolve() !== null;
    }

    private function ocrFallbackEnabled(): bool
    {
        return Settings::get('plans.ocr_fallback_enabled', config('plans.ocr_fallback_enabled', false))->toBool();
    }
}
