<?php

namespace App\Domains\Documents\Services;

use App\Core\Assets\Files\Contracts\FilePathNormalizerContract;
use App\Core\Assets\Files\Contracts\FileStorageContract;
use App\Core\Identity\Models\User;
use App\Core\Settings\Facades\Settings;
use App\Domains\Documents\Contracts\DocumentOrchestratorContract;
use App\Domains\Documents\Models\Document;
use App\Domains\Projects\Models\Project;
use Illuminate\Http\UploadedFile;

class DocumentService implements DocumentOrchestratorContract
{
    public function __construct(
        private readonly FileStorageContract $fileStorage,
        private readonly FilePathNormalizerContract $filePathNormalizer,
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
        $storedPath = $this->fileStorage->store($file, $this->storageFolder('documents/user/'.$owner->id, $folderPath), $disk);

        $document = Document::query()->create([
            'title' => (string) ($attributes['title'] ?? pathinfo($originalName, PATHINFO_FILENAME)),
            'description' => $attributes['description'] ?? null,
            'folder_path' => $folderPath,
            'original_name' => $originalName,
            'stored_name' => basename((string) $storedPath),
            'extension' => $extension,
            'mime_type' => $mimeType,
            'file_size' => $fileSize,
            'storage_disk' => $disk,
            'storage_path' => $storedPath,
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
        $storedPath = $this->fileStorage->store($file, $this->storageFolder('documents/project/'.$project->id, $folderPath), $disk);

        $document = Document::query()->create([
            'title' => (string) ($attributes['title'] ?? pathinfo($originalName, PATHINFO_FILENAME)),
            'description' => $attributes['description'] ?? null,
            'folder_path' => $folderPath,
            'original_name' => $originalName,
            'stored_name' => basename((string) $storedPath),
            'extension' => $extension,
            'mime_type' => $mimeType,
            'file_size' => $fileSize,
            'storage_disk' => $disk,
            'storage_path' => $storedPath,
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

        $oldPath = $document->storage_path;
        $storedPath = $this->fileStorage->store($file, $folder, $disk);

        $document->fill([
            'folder_path' => $folderPath,
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
            $this->fileStorage->delete((string) $oldPath, $disk);
        }

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
        if (filled($document->storage_path)) {
            $this->fileStorage->delete($document->storage_path, (string) $document->storage_disk);
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
