<?php

declare(strict_types=1);

namespace App\Core\Assets\Contracts;

/**
 * Normalizes caller-supplied folder hints into safe storage path segments.
 */
interface FilePathNormalizerContract
{
    /**
     * Returns a normalized relative folder path, or null when the input is empty
     * or resolves to nothing safe.
     */
    public function normalize(mixed $path): ?string;
}
