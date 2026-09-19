<?php

declare(strict_types=1);

namespace App\Domains\Plans\Services;

use App\Domains\Plans\Contracts\SheetMetadataExtractorContract;

/**
 * No-op OCR driver bound in the testing environment so tests never make real
 * calls to an external OCR provider.
 */
final class NullOcrExtractor implements SheetMetadataExtractorContract
{
    public function extract(string $absolutePdfPath, int $page): array
    {
        return ['sheet_number' => null, 'title' => null, 'confidence' => 0.0, 'source' => 'null', 'text' => ''];
    }
}
