<?php

declare(strict_types=1);

namespace App\Domains\Plans\Services;

use App\Core\Settings\Facades\Settings;

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
        $titleBlockMetadata = $this->detectTitleBlockMetadata($text, $pattern);
        preg_match_all('/'.trim($pattern, '/').'/i', $text, $matches, PREG_OFFSET_CAPTURE);

        $number = $titleBlockMetadata['sheet_number'] ?? $this->findSheetNumber($text, $matches[0] ?? []);
        $title = $titleBlockMetadata['title'] ?? $this->findTitle($text, $number);
        $confidence = $number === null ? 0.0 : ($title === null ? 0.65 : 0.9);

        return ['sheet_number' => $number, 'title' => $title, 'confidence' => $confidence];
    }

    /**
     * @return array{sheet_number:?string,title:?string}
     */
    private function detectTitleBlockMetadata(string $text, string $pattern): array
    {
        $lines = collect(preg_split('/\R+/', $text) ?: [])
            ->map(static fn (string $line): string => trim($line))
            ->values();

        $sheetNameIndex = $lines->search(static fn (string $line): bool => preg_match('/^SHEET\s+NAME$/i', $line) === 1);
        $sheetNumberIndex = $lines->search(static fn (string $line): bool => preg_match('/^SHEET\s+NUMBER$/i', $line) === 1);

        if ($sheetNameIndex === false || $sheetNumberIndex === false || $sheetNumberIndex <= $sheetNameIndex) {
            return ['sheet_number' => null, 'title' => null];
        }

        $title = $lines
            ->slice($sheetNameIndex + 1, $sheetNumberIndex - $sheetNameIndex - 1)
            ->filter(static fn (string $line): bool => $line !== '')
            ->implode(' ');

        $sheetNumberLine = $lines
            ->slice($sheetNumberIndex + 1)
            ->first(static fn (string $line): bool => $line !== '');

        return [
            'sheet_number' => $this->extractSheetNumberFromLabeledLine($sheetNumberLine, $pattern),
            'title' => $title !== '' ? $title : null,
        ];
    }

    private function extractSheetNumberFromLabeledLine(?string $line, string $pattern): ?string
    {
        if ($line === null) {
            return null;
        }

        if (preg_match('/'.trim($pattern, '/').'/i', $line, $match) === 1) {
            return strtoupper(trim($match[0]));
        }

        return trim($line) !== '' ? strtoupper(trim($line)) : null;
    }

    /**
     * @param  array<int, array{0:string, 1:int}>  $matches
     */
    private function findSheetNumber(string $text, array $matches): ?string
    {
        if ($matches === []) {
            return null;
        }

        $textLength = max(strlen($text), 1);
        $lines = preg_split('/\R+/', $text) ?: [];

        return collect($matches)
            ->filter(fn (array $match): bool => ! $this->hasRejectedPrefix($match[0]))
            ->map(function (array $match) use ($lines, $text, $textLength): array {
                $candidate = strtoupper(trim($match[0]));
                $offset = $match[1];
                $lineNumber = substr_count(substr($text, 0, $offset), PHP_EOL);
                $line = strtoupper(trim($lines[$lineNumber] ?? ''));
                $nearbyText = strtoupper(implode(' ', array_slice($lines, max(0, $lineNumber - 8), 18)));

                return [
                    'number' => $candidate,
                    'offset' => $offset,
                    'score' => $this->scoreCandidate($candidate, $line, $nearbyText, $offset, $textLength),
                ];
            })
            ->groupBy('number')
            ->map(static fn ($candidates): array => $candidates
                ->sortBy([
                    ['score', 'desc'],
                    ['offset', 'desc'],
                ])
                ->first())
            ->sortBy([
                ['score', 'desc'],
                ['offset', 'desc'],
                fn (array $candidateA, array $candidateB): int => strlen($candidateB['number']) <=> strlen($candidateA['number']),
            ])
            ->value('number');
    }

    private function hasRejectedPrefix(string $candidate): bool
    {
        preg_match('/^[A-Z]{2,3}/i', trim($candidate), $matches);

        return in_array(strtoupper($matches[0] ?? ''), ['AND', 'FOR', 'REF', 'SEE', 'THE'], true);
    }

    private function scoreCandidate(string $candidate, string $line, string $nearbyText, int $offset, int $textLength): int
    {
        $score = strlen($candidate);

        if (preg_match('/\bSHEET\s*(?:NO\.?|NUMBER|#)\b/', $nearbyText) === 1) {
            $score += 100;
        }

        if (preg_match('/\b(?:SHEET NAME|SCALE|PROJECT NUMBER|DRAWING NUMBER)\b/', $nearbyText) === 1) {
            $score += 60;
        }

        if ($offset / $textLength >= 0.6) {
            $score += 30;
        }

        if (preg_match('/[\s.-]/', $candidate) === 1) {
            $score += 15;
        }

        if (preg_match('/\b(?:SEE|REFER|REFERENCE|DETAIL|SCHEDULE)\b/', $line) === 1) {
            $score -= 50;
        }

        return $score;
    }

    private function findTitle(string $text, ?string $number): ?string
    {
        $upperNumber = $number === null ? null : strtoupper($number);

        $lines = collect(preg_split('/\R+/', $text) ?: [])
            ->map(static fn (string $line): string => trim($line))
            ->filter(static fn (string $line): bool => $line !== '')
            ->reject(static fn (string $line): bool => $upperNumber !== null && str_contains(strtoupper($line), $upperNumber));

        return $lines
            ->filter(static fn (string $line): bool => strlen($line) >= 4 && strlen($line) <= 120)
            ->sortByDesc(static fn (string $line): int => strlen($line))
            ->first();
    }
}
