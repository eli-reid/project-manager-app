<?php

namespace App\Core\Assets\Files\Contracts;

interface FilePathNormalizerContract
{
    public function normalize(mixed $path): ?string;
}
