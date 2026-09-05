<?php

<<<<<<< HEAD
declare(strict_types=1);

namespace App\Core\Assets\Services;

use App\Core\Assets\Contracts\AssetOrchestratorContract;
use App\Core\Assets\Contracts\FilePathNormalizerContract;
use App\Core\Assets\Contracts\FileStorageContract;
use App\Core\Assets\DTOs\AssetMeta;
use App\Core\Assets\DTOs\AssetReferenceTarget;
use App\Core\Assets\Models\Asset;
use App\Core\Assets\Models\AssetReference;
use App\Core\Identity\Models\User;
use App\Core\Settings\Facades\Settings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class AssetService implements AssetOrchestratorContract
{
    private const DEFAULT_DIRECTORY = 'assets';

    public function __construct(
        private readonly FileStorageContract $fileStorage,
        private readonly FilePathNormalizerContract $pathNormalizer,
        private readonly AssetReferencerRegistry $registry,
    ) {}

    public function upload(
        User $uploader,
        UploadedFile $file,
        AssetReferenceTarget $target,
        ?AssetMeta $meta = null,
    ): Asset {
        $folder = $this->pathNormalizer->normalize($meta?->folderPath);
        $disk = $meta?->disk ?? $this->defaultDisk();
        $hash = hash_file('sha256', $file->getRealPath()) ?: null;

        return DB::transaction(function () use ($uploader, $file, $target, $meta, $folder, $disk, $hash): Asset {
            if (($meta?->dedupeByHash ?? $this->dedupeEnabledByDefault()) && $hash !== null) {
                $existing = Asset::query()
                    ->where('storage_disk', $disk)
                    ->where('content_hash', $hash)
                    ->first();

                if ($existing instanceof Asset && $this->fileStorage->exists((string) $existing->storage_path, $disk)) {
                    $this->attachReference($existing, $target, $uploader);

                    return $existing->fresh();
                }
            }

            $asset = Asset::query()->create([
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'size_bytes' => $file->getSize(),
                'storage_disk' => $disk,
                'storage_path' => $this->fileStorage->store($file, $folder ?? self::DEFAULT_DIRECTORY, $disk),
                'folder_path' => $folder,
                'content_hash' => $hash,
                'created_by_id' => $uploader->getAuthIdentifier(),
            ]);

            $this->attachReference($asset, $target, $uploader);

            return $asset->fresh();
        });
    }

    public function replaceFile(Asset $asset, UploadedFile $file, ?AssetMeta $meta = null): Asset
    {
        $folder = $this->pathNormalizer->normalize($meta?->folderPath ?? $asset->folder_path);
        $disk = $meta?->disk ?? $asset->storage_disk ?? $this->defaultDisk();

        $previousPath = (string) $asset->storage_path;
        $previousDisk = (string) $asset->storage_disk;

        $asset->fill([
=======
namespace App\Core\Assets\Services;

use App\Core\Assets\Contracts\AssetOrchestratorContract;
use App\Core\Assets\DTOs\AssetMeta;
use App\Core\Assets\Models\Asset;
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

    public function uploadAsset(Authenticatable $uploader, UploadedFile $file, ?AssetMeta $meta = null): Asset
    {
        $folder = $this->pathNormalizer->normalize($meta?->folderPath ?? null) ?? null;

        $disk = $meta?->disk ?? Config::get('filesystems.default');

        $directory = $folder !== null ? $folder : 'assets';

        $storagePath = $this->fileStorage->store($file, $directory, $disk);

        $asset = Asset::create([
>>>>>>> production
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'size_bytes' => $file->getSize(),
            'storage_disk' => $disk,
<<<<<<< HEAD
            'storage_path' => $this->fileStorage->store($file, $folder ?? self::DEFAULT_DIRECTORY, $disk),
            'folder_path' => $folder,
            'content_hash' => hash_file('sha256', $file->getRealPath()) ?: null,
=======
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
>>>>>>> production
        ]);

        $asset->save();

<<<<<<< HEAD
        if ($previousPath !== '' && $previousPath !== $asset->storage_path) {
            $this->fileStorage->delete($previousPath, $previousDisk);
=======
        if (! empty($oldPath)) {
            $this->fileStorage->delete($oldPath, $disk);
>>>>>>> production
        }

        return $asset->fresh();
    }

<<<<<<< HEAD
    public function move(Asset $asset, ?string $folderPath): Asset
    {
        $folder = $this->pathNormalizer->normalize($folderPath);
        $disk = (string) ($asset->storage_disk ?? $this->defaultDisk());
        $targetPath = ($folder ?? self::DEFAULT_DIRECTORY).'/'.basename((string) $asset->storage_path);

        if (filled($asset->storage_path) && $asset->storage_path !== $targetPath) {
=======
    public function moveAsset(Asset $asset, ?string $folderPath): Asset
    {
        $folder = $this->pathNormalizer->normalize($folderPath);
        $disk = $asset->storage_disk ?? Config::get('filesystems.default');

        $directory = $folder !== null ? $folder : 'assets';

        $filename = basename((string) $asset->storage_path);
        $targetPath = $directory . '/' . $filename;

        if (! empty($asset->storage_path) && $asset->storage_path !== $targetPath) {
>>>>>>> production
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

<<<<<<< HEAD
    public function release(Asset $asset, AssetReferenceTarget $target): bool
    {
        return DB::transaction(function () use ($asset, $target): bool {
            $asset->references()
                ->where('referencer_type', $target->referencerType)
                ->where('referencer_id', $target->referencerId)
                ->where('role', $target->role)
                ->delete();

            if ($asset->references()->exists()) {
                return true;
            }

            return $this->purge($asset);
        });
    }

    public function purge(Asset $asset): bool
    {
        try {
            if (filled($asset->storage_path)) {
=======
    public function deleteAsset(Asset $asset): bool
    {
        try {
            if (! empty($asset->storage_path)) {
>>>>>>> production
                $this->fileStorage->delete((string) $asset->storage_path, (string) $asset->storage_disk);
            }

            $asset->delete();

            return true;
<<<<<<< HEAD
        } catch (\Throwable) {
=======
        } catch (\Throwable $e) {
>>>>>>> production
            return false;
        }
    }

<<<<<<< HEAD
    public function validationRules(?string $referencerType = null): array
    {
        if ($referencerType !== null) {
            $override = $this->registry->validationRulesFor($referencerType);

            if ($override !== null) {
                return $override;
            }
        }

        return [
            'max_kilobytes' => max(1, Settings::get('assets.max_file_size', 10240)->toInt()),
            'allowed_extensions' => $this->allowedExtensions(),
        ];
    }

    private function attachReference(Asset $asset, AssetReferenceTarget $target, User $uploader): AssetReference
    {
        return AssetReference::query()->firstOrCreate(
            [
                'asset_id' => $asset->id,
                ...$target->toAttributes(),
            ],
            ['created_by_id' => $uploader->getAuthIdentifier()],
        );
    }

    /**
     * @return array<int, string>
     */
    private function allowedExtensions(): array
    {
        $raw = Settings::get('assets.allowed_types', 'pdf,doc,docx,jpg,jpeg,png')->toString();

        $extensions = collect(explode(',', $raw))
            ->map(fn (string $extension): string => trim(strtolower($extension)))
            ->filter()
            ->values()
            ->all();

        return $extensions === [] ? ['pdf'] : $extensions;
    }

    private function defaultDisk(): string
    {
        return Settings::get('assets.storage_disk', 'local')->toString();
    }

    private function dedupeEnabledByDefault(): bool
    {
        return Settings::get('assets.deduplicate', 'true')->toBool(true);
    }
}
=======
    public function updateHints(Asset $asset, ?AssetMeta $meta): Asset
    {
        $folder = $this->pathNormalizer->normalize($meta?->folderPath ?? $asset->folder_path ?? null);
        $disk = $meta?->disk ?? $asset->storage_disk ?? Config::get('filesystems.default');

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

    public function validationRules(): array
    {
        return [
            'max_kilobytes' => 10240,
            'allowed_extensions' => ['pdf', 'jpg', 'jpeg', 'png', 'zip', 'doc', 'docx'],
        ];
    }
}
>>>>>>> production
