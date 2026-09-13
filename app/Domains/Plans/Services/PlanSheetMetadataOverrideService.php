<?php

declare(strict_types=1);

namespace App\Domains\Plans\Services;

use App\Domains\Plans\Models\PlanSheetRevision;

final class PlanSheetMetadataOverrideService
{
    public function apply(PlanSheetRevision $revision, string $sheetNumber, ?string $title = null): PlanSheetRevision
    {
        $revision->update([
            'detected_sheet_number' => strtoupper(trim($sheetNumber)),
            'detected_title' => $title !== null ? trim($title) : $revision->detected_title,
            'detection_confidence' => 1,
            'detection_source' => 'manual',
        ]);

        return $revision->fresh();
    }
}
