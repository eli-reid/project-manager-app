<?php

namespace App\Core\Assets\Files\Services;

use App\Core\Assets\Files\Contracts\FilePathNormalizerContract;

class DefaultFilePathNormalizer implements FilePathNormalizerContract
{
    public function normalize(mixed $path): ?string
    {
        if (! is_string($path)) {
            return null;
        }

        $normalized = trim(str_replace('\\', '/', $path));

        if ($normalized === '') {
            return null;
        }

        $segments = array_values(array_filter(array_map(
            static fn (string $segment): string => trim($segment),
            explode('/', $normalized),
        ), static fn (string $segment): bool => $segment !== '' && $segment !== '.' && $segment !== '..'));

        if ($segments === []) {
            return null;
        }

        return implode('/', $segments);
    }
}
