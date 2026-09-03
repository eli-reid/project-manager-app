<?php

declare(strict_types=1);

namespace App\Core\Assets\Contracts;

use Illuminate\Http\UploadedFile;

/**
 * Physical file persistence. Knows nothing about business metadata,
 * ownership, or authorization.
 */
interface FileStorageContract
{
    /**
     * Persist an uploaded file and return its canonical storage path.
     */
    public function store(UploadedFile $file, string $directory, string $disk): string;

    /**
     * Move a stored object. Returns true when the object was moved.
     */
    public function move(string $fromPath, string $toPath, string $disk): bool;

    /**
     * Delete a stored object. Idempotent: safe when the object is missing.
     */
    public function delete(string $path, string $disk): bool;

    public function exists(string $path, string $disk): bool;
}
