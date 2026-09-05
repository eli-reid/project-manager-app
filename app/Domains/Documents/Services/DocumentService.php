<?php

namespace App\Domains\Documents\Services;

<<<<<<< HEAD
use App\Core\Assets\Contracts\AssetOrchestratorContract;
use App\Core\Assets\DTOs\AssetMeta;
use App\Core\Assets\DTOs\AssetReferenceTarget;
=======
use App\Core\Files\Contracts\FilePathNormalizerContract;
use App\Core\Files\Contracts\FileStorageContract;
>>>>>>> production
use App\Core\Identity\Models\User;
use App\Core\Settings\Facades\Settings;
use App\Domains\Documents\Contracts\DocumentOrchestratorContract;
use App\Domains\Documents\Models\Document;
use App\Domains\Projects\Models\Project;
use Illuminate\Http\UploadedFile;

class DocumentService implements DocumentOrchestratorContract
{
    public function __construct(
<<<<<<< HEAD
        private readonly AssetOrchestratorContract $orchestrator,
=======
        private readonly FileStorageContract $fileStorage,
        private readonly FilePathNormalizerContract $filePathNormalizer,
>>>>>>> production
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function uploadUserDocument(User $owner, UploadedFile $file, array $attributes = []): Document
    {
        $originalName = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();
        $mimeType = (string) $file->getClientMimeType();
        $fileSize = (int) $file->getSize();
        $folderPath = $this->normalizeFolderPath($attributes['folder_path'] ?? null);

        $disk = $this->storageDisk();
<<<<<<< HEAD
        $folderPath = 'documents/user/'.$owner->id;

        // Upload through Assets orchestrator
        $asset = $this->orchestrator->upload(
            $owner,
            $file,
            new AssetReferenceTarget('documents', 'doc-'.$owner->id, 'primary'),
            AssetMeta::fromArray([
                'folder_path' => $folderPath,
                'disk' => $disk,
            ]),
        );
=======
        $storedPath = $this->fileStorage->store($file, $this->storageFolder('documents/user/'.$owner->id, $folderPath), $disk);
>>>>>>> production

        $document = Document::query()->create([
            'title' => (string) ($attributes['title'] ?? pathinfo($originalName, PATHINFO_FILENAME)),
            'description' => $attributes['description'] ?? null,
            'folder_path' => $folderPath,
            'original_name' => $originalName,
            'stored_name' => basename((string) $asset->storage_path),
            'extension' => $extension,
            'mime_type' => $mimeType,
            'file_size' => $fileSize,
            'storage_disk' => $disk,
            'storage_path' => $asset->storage_path,
            'asset_id' => $asset->id,
            'owner_scope' => Document::OWNER_SCOPE_USER,
            'owner_id' => $owner->id,
            'visibility' => Document::VISIBILITY_PRIVATE,
            'replace_mode' => $this->replaceBehavior(),
            'uploaded_by_id' => $owner->id,
        ]);

        return $document->fresh();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function uploadProjectDocument(Project $project, User $actor, UploadedFile $file, array $attributes = []): Document
    {
        $originalName = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();
        $mimeType = (string) $file->getClientMimeType();
        $fileSize = (int) $file->getSize();
        $folderPath = $this->normalizeFolderPath($attributes['folder_path'] ?? null);

        $disk = $this->storageDisk();
<<<<<<< HEAD
        $folderPath = 'documents/project/'.$project->id;

        // Upload through Assets orchestrator
        $asset = $this->orchestrator->upload(
            $actor,
            $file,
            new AssetReferenceTarget('documents', 'doc-'.$project->id, 'primary'),
            AssetMeta::fromArray([
                'folder_path' => $folderPath,
                'disk' => $disk,
            ]),
        );
=======
        $storedPath = $this->fileStorage->store($file, $this->storageFolder('documents/project/'.$project->id, $folderPath), $disk);
>>>>>>> production

        $document = Document::query()->create([
            'title' => (string) ($attributes['title'] ?? pathinfo($originalName, PATHINFO_FILENAME)),
            'description' => $attributes['description'] ?? null,
            'folder_path' => $folderPath,
            'original_name' => $originalName,
            'stored_name' => basename((string) $asset->storage_path),
            'extension' => $extension,
            'mime_type' => $mimeType,
            'file_size' => $fileSize,
            'storage_disk' => $disk,
            'storage_path' => $asset->storage_path,
            'asset_id' => $asset->id,
            'owner_scope' => Document::OWNER_SCOPE_PROJECT,
            'owner_id' => $project->id,
            'visibility' => Document::VISIBILITY_PROJECT,
            'replace_mode' => $this->replaceBehavior(),
            'uploaded_by_id' => $actor->id,
        ]);

        return $document->fresh();
    }

    public function replaceFile(Document $document, UploadedFile $file, ?User $actor = null, ?string $folderPath = null): Document
    {
        $originalName = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();
        $mimeType = (string) $file->getClientMimeType();
        $fileSize = (int) $file->getSize();
        $folderPath = $this->normalizeFolderPath($folderPath ?? $document->folder_path);

        $disk = $this->storageDisk();
        $folder = $this->storageFolder(
            $document->isProjectOwned()
                ? 'documents/project/'.($document->owner_id ?? 'unknown')
                : 'documents/user/'.($document->owner_id ?? 'unknown'),
            $folderPath,
        );

<<<<<<< HEAD
        if ($actor === null) {
            $actor = $document->uploadedBy;
        }

        // Replace through Assets orchestrator if asset exists
        if ($document->asset_id !== null && $document->asset !== null) {
            $asset = $this->orchestrator->replaceFile(
                $document->asset,
                $file,
                AssetMeta::fromArray(['folder_path' => $folder, 'disk' => $disk]),
            );
        } else {
            // Fallback for documents without assets (shouldn't happen in Phase 2+)
            $oldPath = $document->storage_path;
            $storedPath = $file->store($folder, $disk);

            $document->fill([
                'original_name' => $originalName,
                'stored_name' => basename((string) $storedPath),
                'extension' => $extension,
                'mime_type' => $mimeType,
                'file_size' => $fileSize,
                'storage_disk' => $disk,
                'storage_path' => $storedPath,
                'replace_mode' => $this->replaceBehavior(),
                'last_replaced_at' => now(),
            ]);

            if ($actor !== null) {
                $document->uploaded_by_id = $actor->id;
            }

            $document->save();

            if ($this->replaceBehavior() === Document::REPLACE_MODE_REPLACE && filled($oldPath)) {
                Storage::disk($disk)->delete((string) $oldPath);
            }

            return $document->fresh();
        }
=======
        $oldPath = $document->storage_path;
        $storedPath = $this->fileStorage->store($file, $folder, $disk);
>>>>>>> production

        $document->fill([
            'folder_path' => $folderPath,
            'original_name' => $originalName,
            'stored_name' => basename((string) $asset->storage_path),
            'extension' => $extension,
            'mime_type' => $mimeType,
            'file_size' => $fileSize,
            'storage_disk' => $disk,
            'storage_path' => $asset->storage_path,
            'replace_mode' => $this->replaceBehavior(),
            'last_replaced_at' => now(),
            'uploaded_by_id' => $actor->id,
        ]);

        $document->save();

<<<<<<< HEAD
=======
        if ($this->replaceBehavior() === Document::REPLACE_MODE_REPLACE && filled($oldPath)) {
            $this->fileStorage->delete((string) $oldPath, $disk);
        }

>>>>>>> production
        return $document->fresh();
    }

    public function moveDocument(Document $document, ?string $folderPath = null): Document
    {
        $disk = $this->storageDisk();
        $folderPath = $this->normalizeFolderPath($folderPath);
        $folder = $this->storageFolder(
            $document->isProjectOwned()
                ? 'documents/project/'.($document->owner_id ?? 'unknown')
                : 'documents/user/'.($document->owner_id ?? 'unknown'),
            $folderPath,
        );
        $storedPath = $folder.'/'.($document->stored_name ?: basename((string) $document->storage_path));

        if (filled($document->storage_path) && $document->storage_path !== $storedPath) {
            $this->fileStorage->move((string) $document->storage_path, $storedPath, $disk);
        }

        $document->fill([
            'folder_path' => $folderPath,
            'storage_disk' => $disk,
            'storage_path' => $storedPath,
        ]);

        $document->save();

        return $document->fresh();
    }

    public function deleteDocument(Document $document): void
    {
<<<<<<< HEAD
        // Delete through Assets orchestrator if asset exists
        if ($document->asset_id !== null && $document->asset !== null) {
            $this->orchestrator->purge($document->asset);
        } elseif (filled($document->storage_path)) {
            // Fallback for documents without assets
            Storage::disk($document->storage_disk)->delete($document->storage_path);
=======
        if (filled($document->storage_path)) {
            $this->fileStorage->delete($document->storage_path, (string) $document->storage_disk);
>>>>>>> production
        }

        $document->delete();
    }

    /**
     * @return array{max_kilobytes:int, allowed_extensions:array<int, string>}
     */
    public function validationRules(): array
    {
        $rawAllowedTypes = Settings::get('documents.allowed_types', 'pdf,doc,docx,jpg,jpeg,png')->toString();
        $allowedExtensions = collect(explode(',', $rawAllowedTypes))
            ->map(fn (string $extension): string => trim(strtolower($extension)))
            ->filter()
            ->values()
            ->all();

        $maxKilobytes = max(1, Settings::get('documents.max_file_size', 10240)->toInt());

        if ($allowedExtensions === []) {
            $allowedExtensions = ['pdf'];
        }

        return [
            'max_kilobytes' => $maxKilobytes,
            'allowed_extensions' => $allowedExtensions,
        ];
    }

    private function storageDisk(): string
    {
        return Settings::get('documents.storage_disk', 'local')->toString();
    }

    private function storageFolder(string $baseFolder, ?string $folderPath = null): string
    {
        if ($folderPath === null) {
            return $baseFolder;
        }

        return $baseFolder.'/'.$folderPath;
    }

    private function normalizeFolderPath(mixed $folderPath): ?string
    {
        return $this->filePathNormalizer->normalize($folderPath);
    }

    private function replaceBehavior(): string
    {
        $replaceBehavior = Settings::get('documents.replace_behavior', Document::REPLACE_MODE_REPLACE)->toString();

        return in_array($replaceBehavior, [Document::REPLACE_MODE_REPLACE, Document::REPLACE_MODE_KEEP_HISTORY], true)
            ? $replaceBehavior
            : Document::REPLACE_MODE_REPLACE;
    }
}
