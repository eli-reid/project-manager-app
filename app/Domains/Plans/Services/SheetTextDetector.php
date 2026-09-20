<?php

declare(strict_types=1);

namespace App\Domains\Plans\Services;

use App\Core\Settings\Facades\Settings;
use Illuminate\Support\Str;

/**
 * Detects a sheet number and title from raw page text, regardless of whether
 * the text came from the PDF text layer or an OCR pass, so both sources are
 * scored with the same rules.
 */
final class SheetTextDetector
{
    /**
     * @return array{sheet_number:?string,title:?string,confidence:float}
     */
    public function detect(string $text): array
    {
        $pattern = Settings::get('plans.sheet_number_pattern', config('plans.sheet_number_pattern'))->toString();
        preg_match_all('/'.trim($pattern, '/').'/i', $text, $matches);

        $number = collect($matches[0] ?? [])
            ->map(static fn (string $candidate): string => strtoupper(trim($candidate)))
            ->unique()
            ->sortByDesc(static fn (string $candidate): int => strlen($candidate))
            ->first();
        $title = $this->findTitle($text, $number);
        $confidence = $number === null ? 0.0 : ($title === null ? 0.65 : 0.9);

        return ['sheet_number' => $number, 'title' => $title, 'confidence' => $confidence];
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
