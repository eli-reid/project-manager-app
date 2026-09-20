<?php

declare(strict_types=1);

namespace App\Domains\Plans\Services;

use Illuminate\Support\Facades\File;
use Throwable;

final class PlanTextExtractionLogger
{
    public function record(string $source, string $absolutePdfPath, int $page, string $text): void
    {
        try {
            File::ensureDirectoryExists(storage_path('logs'));

            File::append(storage_path('logs/plan-text-extraction.log'), implode(PHP_EOL, [
                '['.now()->toIso8601String().']',
                'source: '.$source,
                'file: '.basename($absolutePdfPath),
                'page: '.$page,
                'text_length: '.mb_strlen($text),
                '--- BEGIN FULL TEXT ---',
                $text,
                '--- END FULL TEXT ---',
                '',
            ]).PHP_EOL);
        } catch (Throwable) {
            // Do not fail plan processing when the debug extraction log is unavailable.
        }
    }
}
