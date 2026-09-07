<?php

declare(strict_types=1);

namespace App\Domains\Plans\Services;

use App\Domains\Plans\Models\PlanSheetRevision;
use Illuminate\Support\Str;
use Smalot\PdfParser\Parser;

final class PlanSheetMetadataExtractor
{
    public function __construct(private readonly Parser $parser) {}

    public function extract(PlanSheetRevision $revision, string $absolutePdfPath): PlanSheetRevision
    {
        $revision->loadMissing('set');
        $pdf = $this->parser->parseFile($absolutePdfPath);
        $text = trim($pdf->getPages()[$revision->page_number - 1]?->getText() ?? '');
        $pattern = (string) config('plans.sheet_number_pattern');
        preg_match_all('/'.trim($pattern, '/').'/i', $text, $matches);

        $number = collect($matches[0] ?? [])
            ->map(static fn (string $candidate): string => strtoupper(trim($candidate)))
            ->unique()
            ->sortByDesc(static fn (string $candidate): int => strlen($candidate))
            ->first();
        $title = $this->findTitle($text, $number);
        $confidence = $number === null ? 0 : ($title === null ? 0.65 : 0.9);

        if ($revision->detection_source === 'manual') {
            return $revision;
        }

        $revision->update([
            'text_layer' => $text,
            'detected_sheet_number' => $number,
            'detected_title' => $title,
            'detection_confidence' => $confidence,
            'detection_source' => 'text-layer',
        ]);

        return $revision->fresh();
    }

    private function findTitle(string $text, ?string $number): ?string
    {
        $lines = collect(preg_split('/\R+/', $text) ?: [])
            ->map(static fn (string $line): string => trim($line))
            ->filter(static fn (string $line): bool => $line !== '')
            ->reject(static fn (string $line): bool => $number !== null && Str::contains(strtoupper($line), $number));

        return $lines
            ->filter(static fn (string $line): bool => strlen($line) >= 4 && strlen($line) <= 120)
            ->sortByDesc(static fn (string $line): int => strlen($line))
            ->first();
    }
}
