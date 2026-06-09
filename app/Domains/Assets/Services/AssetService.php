<?php

namespace App\Domains\Assets\Services;

use App\Domains\Assets\Contracts\AssetOrchestratorContract;
use App\Domains\Assets\Models\Asset;
use App\Core\Files\Contracts\FileStorageContract;
use App\Core\Files\Contracts\FilePathNormalizerContract;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;

class AssetService implements AssetOrchestratorContract
{
    public function __construct(
        private readonly FileStorageContract $fileStorage,
        private readonly FilePathNormalizerContract $pathNormalizer,
    ) {
    }

    public function uploadAsset(Authenticatable $uploader, UploadedFile $file, array $meta = []): Asset
    {
        $folder = $this->pathNormalizer->normalize($meta['folder_path'] ?? null) ?? null;

        $disk = $meta['disk'] ?? Config::get('filesystems.default');

        $directory = $folder !== null ? $folder : 'assets';

        $storagePath = $this->fileStorage->store($file, $directory, $disk);

        $asset = Asset::create([
            'title' => $meta['title'] ?? null,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'size_bytes' => $file->getSize(),
            'storage_disk' => $disk,
            'storage_path' => $storagePath,
            'folder_path' => $folder,
            'created_by_id' => $uploader->getAuthIdentifier(),
        ]);

        return $asset;
    }

    public function replaceFile(Asset $asset, UploadedFile $file): Asset
    {
        $originalName = $file->getClientOriginalName();
        $mimeType = (string) $file->getClientMimeType();
        $fileSize = (int) $file->getSize();

        $folder = $this->pathNormalizer->normalize($asset->folder_path ?? null);
        $disk = $asset->storage_disk ?? Config::get('filesystems.default');

        $directory = $folder !== null ? $folder : 'assets';

        $oldPath = $asset->storage_path;
        $storedPath = $this->fileStorage->store($file, $directory, $disk);

        $asset->fill([
            'folder_path' => $folder,
            'original_name' => $originalName,
            'mime_type' => $mimeType,
            'size_bytes' => $fileSize,
            'storage_disk' => $disk,
            'storage_path' => $storedPath,
        ]);

        $asset->save();

        if (! empty($oldPath)) {
            $this->fileStorage->delete($oldPath, $disk);
        }

        return $asset->fresh();
    }

    public function moveAsset(Asset $asset, ?string $folderPath): Asset
    {
        $folder = $this->pathNormalizer->normalize($folderPath);
        $disk = $asset->storage_disk ?? Config::get('filesystems.default');

        $directory = $folder !== null ? $folder : 'assets';

        $filename = basename((string) $asset->storage_path);
        $targetPath = $directory . '/' . $filename;

        if (! empty($asset->storage_path) && $asset->storage_path !== $targetPath) {
            $this->fileStorage->move((string) $asset->storage_path, $targetPath, $disk);
        }

        $asset->fill([
            'folder_path' => $folder,
            'storage_disk' => $disk,
            'storage_path' => $targetPath,
        ]);

        $asset->save();

        return $asset->fresh();
    }

    public function deleteAsset(Asset $asset): bool
    {
        try {
            if (! empty($asset->storage_path)) {
                $this->fileStorage->delete((string) $asset->storage_path, (string) $asset->storage_disk);
            }

            $asset->delete();

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function validationRules(): array
    {
        return [
            'max_kilobytes' => 10240,
            'allowed_extensions' => ['pdf', 'jpg', 'jpeg', 'png', 'zip', 'doc', 'docx'],
        ];
    }
}