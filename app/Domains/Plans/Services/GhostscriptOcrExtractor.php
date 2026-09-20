<?php

declare(strict_types=1);

namespace App\Domains\Plans\Services;

use App\Domains\Plans\Contracts\SheetMetadataExtractorContract;
use App\Domains\Plans\Services\Support\GhostscriptBinaryLocator;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

/**
 * Free, local OCR driver: crops the sheet's title block (or the full page,
 * when no region is configured) and runs it through Ghostscript's built-in
 * `ocr` device, then reuses the text-layer extractor to pull the recognized
 * text back out of the resulting single-page PDF. Requires a Ghostscript
 * build compiled with Tesseract OCR support (`--with-ocr=tesseract`); the
 * common prebuilt Ghostscript binaries do not include this device.
 */
final class GhostscriptOcrExtractor implements SheetMetadataExtractorContract
{
    public function __construct(
        private readonly PlanTitleBlockRegion $titleBlockRegion,
        private readonly PlanTitleBlockImageCropper $titleBlockImageCropper,
        private readonly GhostscriptPageTextExtractor $textExtractor,
        private readonly SheetTextDetector $detector,
        private readonly PlanTextExtractionLogger $extractionLogger,
    ) {}

    public function extract(string $absolutePdfPath, int $page): array
    {
        $binary = GhostscriptBinaryLocator::locate();
        if ($binary === null) {
            throw new RuntimeException('Ghostscript is not available. Configure PLANS_GHOSTSCRIPT_BIN_PATH to the Ghostscript executable used for OCR.');
        }

        $region = $this->titleBlockRegion->resolve() ?? ['x' => 0.0, 'y' => 0.0, 'width' => 1.0, 'height' => 1.0];
        $croppedImagePath = $this->titleBlockImageCropper->crop($absolutePdfPath, $page, $region);
        $ocrPdfPath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'plan-title-block-ocr-'.Str::random(16).'.pdf';

        try {
            $process = new Process([
                $binary,
                '-q',
                '-dNOPAUSE',
                '-dBATCH',
                '-sDEVICE=ocr',
                '-sOCRLanguage=eng',
                '-sOutputFile='.$ocrPdfPath,
                $croppedImagePath,
            ], null, null, null, (int) config('plans.process_timeout', 300));
            $process->mustRun();

            if (! is_file($ocrPdfPath)) {
                throw new RuntimeException('Ghostscript did not produce an OCR output file for the title block.');
            }

            $text = $this->textExtractor->extract($ocrPdfPath, 1);
        } catch (ProcessFailedException $exception) {
            throw new RuntimeException('Ghostscript OCR failed. Ensure the installed Ghostscript build includes the OCR device (compiled with Tesseract support).', previous: $exception);
        } finally {
            if (is_file($croppedImagePath)) {
                unlink($croppedImagePath);
            }
            if (is_file($ocrPdfPath)) {
                unlink($ocrPdfPath);
            }
        }

        $this->extractionLogger->record('ghostscript-ocr', $absolutePdfPath, $page, $text);
        $detection = $this->detector->detect($text);

        return [
            'sheet_number' => $detection['sheet_number'],
            'title' => $detection['title'],
            'confidence' => $detection['confidence'],
            'source' => 'ghostscript-ocr',
            'text' => $text,
        ];
    }
}
