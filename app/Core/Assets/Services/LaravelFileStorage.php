<?php

declare(strict_types=1);

namespace App\Core\Assets\Services;

use App\Core\Assets\Contracts\FileStorageContract;
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
        $filesystem = Storage::disk($disk);

        if (! $filesystem->exists($fromPath)) {
            return false;
        }

        return $filesystem->move($fromPath, $toPath);
    }

    public function delete(string $path, string $disk): bool
    {
        $filesystem = Storage::disk($disk);

        if (! $filesystem->exists($path)) {
            return true;
        }

        return $filesystem->delete($path);
    }

    public function exists(string $path, string $disk): bool
    {
        return Storage::disk($disk)->exists($path);
    }
}
