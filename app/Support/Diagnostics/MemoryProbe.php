<?php

namespace App\Support\Diagnostics;

use Countable;
use Illuminate\Support\Collection;
use Throwable;

final class MemoryProbe
{
    /**
     * Capture the current process memory snapshot.
     *
     * @return array{label:string|null,current_bytes:int,current_mb:float,real_bytes:int,real_mb:float,peak_bytes:int,peak_mb:float}
     */
    public static function snapshot(?string $label = null): array
    {
        $currentBytes = \memory_get_usage(false);
        $realBytes = \memory_get_usage(true);
        $peakBytes = \memory_get_peak_usage(true);

        return [
            'label' => $label,
            'current_bytes' => $currentBytes,
            'current_mb' => self::toMegabytes($currentBytes),
            'real_bytes' => $realBytes,
            'real_mb' => self::toMegabytes($realBytes),
            'peak_bytes' => $peakBytes,
            'peak_mb' => self::toMegabytes($peakBytes),
        ];
    }

    /**
     * Calculate the memory delta from a previous snapshot.
     *
     * @param  array{label:string|null,current_bytes:int,current_mb:float,real_bytes:int,real_mb:float,peak_bytes:int,peak_mb:float}  $baseline
     * @return array{label:string|null,current_bytes:int,current_mb:float,real_bytes:int,real_mb:float,peak_bytes:int,peak_mb:float,delta_current_bytes:int,delta_current_mb:float,delta_real_bytes:int,delta_real_mb:float,delta_peak_bytes:int,delta_peak_mb:float}
     */
    public static function delta(array $baseline, ?string $label = null): array
    {
        $current = self::snapshot($label);

        $current['delta_current_bytes'] = $current['current_bytes'] - $baseline['current_bytes'];
        $current['delta_current_mb'] = self::toMegabytes($current['delta_current_bytes']);
        $current['delta_real_bytes'] = $current['real_bytes'] - $baseline['real_bytes'];
        $current['delta_real_mb'] = self::toMegabytes($current['delta_real_bytes']);
        $current['delta_peak_bytes'] = $current['peak_bytes'] - $baseline['peak_bytes'];
        $current['delta_peak_mb'] = self::toMegabytes($current['delta_peak_bytes']);

        return $current;
    }

    /**
     * Approximate the payload size for a given value.
     *
     * @return array{label:string|null,type:string,count:int|null,approx_bytes:int,approx_mb:float}
     */
    public static function inspect(mixed $value, ?string $label = null): array
    {
        $approxBytes = self::approximateBytes($value);

        return [
            'label' => $label,
            'type' => self::typeOf($value),
            'count' => self::countOf($value),
            'approx_bytes' => $approxBytes,
            'approx_mb' => self::toMegabytes($approxBytes),
        ];
    }

    /**
     * Rank iterable items by approximate size.
     *
     * @return list<array{key:int|string,type:string,count:int|null,approx_bytes:int,approx_mb:float}>
     */
    public static function largestItems(iterable $items, int $limit = 20): array
    {
        $ranked = [];

        foreach ($items as $key => $value) {
            $approxBytes = self::approximateBytes($value);

            $ranked[] = [
                'key' => $key,
                'type' => self::typeOf($value),
                'count' => self::countOf($value),
                'approx_bytes' => $approxBytes,
                'approx_mb' => self::toMegabytes($approxBytes),
            ];
        }

        \usort($ranked, static fn (array $left, array $right): int => $right['approx_bytes'] <=> $left['approx_bytes']);

        return \array_slice($ranked, 0, \max(1, $limit));
    }

    private static function approximateBytes(mixed $value): int
    {
        try {
            $serialized = \serialize($value);

            return \strlen($serialized);
        } catch (Throwable) {
            try {
                $json = \json_encode($value, \JSON_THROW_ON_ERROR);

                return \strlen($json);
            } catch (Throwable) {
                return 0;
            }
        }
    }

    private static function countOf(mixed $value): ?int
    {
        if (\is_array($value) || $value instanceof Collection) {
            return \count($value);
        }

        if ($value instanceof Countable) {
            return \count($value);
        }

        return null;
    }

    private static function typeOf(mixed $value): string
    {
        if (\is_object($value)) {
            return $value::class;
        }

        return \gettype($value);
    }

    private static function toMegabytes(int $bytes): float
    {
        return \round($bytes / 1024 / 1024, 2);
    }
}
