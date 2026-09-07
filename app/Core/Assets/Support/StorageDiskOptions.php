<?php

declare(strict_types=1);

namespace App\Core\Assets\Support;

use Illuminate\Support\Str;

/**
 * Builds select options for storage disk settings from the configured filesystem disks.
 */
class StorageDiskOptions
{
    /**
     * @return array<string, string>
     */
    public static function all(): array
    {
        return collect(config('filesystems.disks', []))
            ->keys()
            ->mapWithKeys(fn (string $disk): array => [$disk => self::label($disk)])
            ->all();
    }

    private static function label(string $disk): string
    {
        return match ($disk) {
            'local' => 'Local Storage',
            'public' => 'Public Storage',
            's3' => 'Amazon S3',
            default => Str::headline($disk),
        };
    }
}
