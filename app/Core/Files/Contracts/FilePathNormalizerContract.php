<?php

namespace App\Core\Files\Contracts;

interface FilePathNormalizerContract
{
    public function normalize(mixed $path): ?string;
}
