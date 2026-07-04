<?php

declare(strict_types=1);

namespace App\Core\Assets\Contracts;

interface FilePathNormalizerContract
{
    public function normalize(mixed $path): ?string;
}
