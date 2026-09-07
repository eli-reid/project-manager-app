<?php

declare(strict_types=1);

namespace App\Domains\Plans\Services;

use App\Domains\Plans\Contracts\SheetMetadataExtractorContract;

final class TesseractOcrExtractor implements SheetMetadataExtractorContract
{
    public function extract(string $absolutePdfPath, int $page): array
    {
        throw new \RuntimeException('Tesseract OCR is not configured for Plans metadata extraction.');
    }
}
