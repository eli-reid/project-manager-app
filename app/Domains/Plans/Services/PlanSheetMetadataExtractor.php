<?php

declare(strict_types=1);

namespace App\Domains\Plans\Services;

use App\Domains\Plans\Models\PlanSheetRevision;
use Illuminate\Support\Str;

final class PlanSheetMetadataExtractor
{
    public function __construct(private readonly SheetTextDetector $detector) {}

    public function applyText(PlanSheetRevision $revision, string $text): PlanSheetRevision
    {
        $detection = $this->detector->detect($text);

        return $this->applyDetection($revision, [
            'sheet_number' => $detection['sheet_number'],
            'title' => $detection['title'],
            'confidence' => $detection['confidence'],
            'source' => 'text-layer',
            'text' => $text,
        ]);
    }

    /**
     * Applies a pre-computed detection result, such as one produced by an OCR
     * driver, without re-running detection against the raw text.
     *
     * @param  array{sheet_number:?string,title:?string,confidence:float,source:string,text:string}  $detection
     */
    public function applyDetection(PlanSheetRevision $revision, array $detection): PlanSheetRevision
    {
        if ($revision->detection_source === 'manual') {
            return $revision;
        }

        $title = $this->normalizeDetectedTitle($detection['title']);

        $revision->update([
            'text_layer' => $detection['text'],
            'detected_sheet_number' => $detection['sheet_number'],
            'detected_title' => $title,
            'detection_confidence' => $detection['confidence'],
            'detection_source' => $detection['source'],
        ]);

        return $revision->fresh();
    }

    private function normalizeDetectedTitle(?string $title): ?string
    {
        if ($title === null) {
            return null;
        }

        $normalizedTitle = Str::squish($title);

        if ($normalizedTitle === '' || strlen($normalizedTitle) > 255) {
            return null;
        }

        return $normalizedTitle;
    }
}
