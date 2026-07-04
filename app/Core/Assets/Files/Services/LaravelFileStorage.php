<?php

namespace App\Core\Assets\Files\Services;

use App\Core\Assets\Files\Contracts\FileStorageContract;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class LaravelFileStorage implements FileStorageContract
{
    public function store(UploadedFile $file, string $directory, string $disk): string
    {
        return (string) $file->store($directory, $disk);
    }

    public function move(string $fromPath, string $toPath, string $disk): bool
    {
        return Storage::disk($disk)->move($fromPath, $toPath);
    }

    public function delete(string $path, string $disk): bool
    {
        return Storage::disk($disk)->delete($path);
    }
}
