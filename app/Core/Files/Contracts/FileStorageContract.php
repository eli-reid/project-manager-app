<?php

namespace App\Core\Files\Contracts;

use Illuminate\Http\UploadedFile;

interface FileStorageContract
{
    public function store(UploadedFile $file, string $directory, string $disk): string;

    public function move(string $fromPath, string $toPath, string $disk): bool;

    public function delete(string $path, string $disk): bool;
}
