<?php

declare(strict_types=1);

namespace App\Core\Assets\Services;

use App\Core\Assets\Contracts\FilePathNormalizerContract;

class DefaultFilePathNormalizer implements FilePathNormalizerContract
{
    public function normalize(mixed $path): ?string
    {
        if (! is_string($path)) {
            return null;
        }

        $segments = preg_split('#[\\\\/]+#', $path) ?: [];

        $safe = [];

        foreach ($segments as $segment) {
            $segment = trim($segment);

            if ($segment === '' || $segment === '.' || $segment === '..') {
                continue;
            }

            $segment = preg_replace('#[^A-Za-z0-9._\- ]#', '', $segment) ?? '';
            $segment = trim($segment);

            if ($segment === '') {
                continue;
            }

            $safe[] = $segment;
        }

        return $safe === [] ? null : implode('/', $safe);
    }
}
