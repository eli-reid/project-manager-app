<?php

namespace App\Domains\Documents\Services;

use App\Core\Identity\Models\User;
use App\Core\Settings\Facades\Settings;
use App\Domains\Documents\Models\Document;
use App\Domains\Projects\Models\Project;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class DocumentService
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function uploadUserDocument(User $owner, UploadedFile $file, array $attributes = []): Document
    {
        $originalName = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();
        $mimeType = (string) $file->getClientMimeType();
        $fileSize = (int) $file->getSize();

        $disk = $this->storageDisk();
        $storedPath = $file->store('documents/user/'.$owner->id, $disk);

        $document = Document::query()->create([
            'title' => (string) ($attributes['title'] ?? pathinfo($originalName, PATHINFO_FILENAME)),
            'description' => $attributes['description'] ?? null,
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

        $disk = $this->storageDisk();
        $storedPath = $file->store('documents/project/'.$project->id, $disk);

        $document = Document::query()->create([
            'title' => (string) ($attributes['title'] ?? pathinfo($originalName, PATHINFO_FILENAME)),
            'description' => $attributes['description'] ?? null,
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

    public function replaceFile(Document $document, UploadedFile $file, ?User $actor = null): Document
    {
        $originalName = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();
        $mimeType = (string) $file->getClientMimeType();
        $fileSize = (int) $file->getSize();

        $disk = $this->storageDisk();
        $folder = $document->isProjectOwned()
            ? 'documents/project/'.($document->owner_id ?? 'unknown')
            : 'documents/user/'.($document->owner_id ?? 'unknown');

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

    public function deleteDocument(Document $document): void
    {
        if (filled($document->storage_path)) {
            Storage::disk($document->storage_disk)->delete($document->storage_path);
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

    private function replaceBehavior(): string
    {
        $replaceBehavior = Settings::get('documents.replace_behavior', Document::REPLACE_MODE_REPLACE)->toString();

        return in_array($replaceBehavior, [Document::REPLACE_MODE_REPLACE, Document::REPLACE_MODE_KEEP_HISTORY], true)
            ? $replaceBehavior
            : Document::REPLACE_MODE_REPLACE;
    }
}
