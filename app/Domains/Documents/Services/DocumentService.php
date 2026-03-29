<?php

namespace App\Domains\Documents\Services;

use App\Core\User\Models\User;
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
        $disk = $this->storageDisk();
        $storedPath = $file->store('documents/user/'.$owner->id, $disk);

        $document = Document::query()->create([
            'title' => (string) ($attributes['title'] ?? pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)),
            'description' => $attributes['description'] ?? null,
            'original_name' => $file->getClientOriginalName(),
            'stored_name' => basename((string) $storedPath),
            'extension' => $file->getClientOriginalExtension(),
            'mime_type' => (string) $file->getClientMimeType(),
            'file_size' => (int) $file->getSize(),
            'storage_disk' => $disk,
            'storage_path' => $storedPath,
            'owner_scope' => Document::OWNER_SCOPE_USER,
            'visibility' => Document::VISIBILITY_PRIVATE,
            'replace_mode' => $this->replaceBehavior(),
            'uploaded_by_id' => $owner->id,
        ]);

        $document->ownerUsers()->sync([$owner->id]);

        return $document->fresh();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function uploadProjectDocument(Project $project, User $actor, UploadedFile $file, array $attributes = []): Document
    {
        $disk = $this->storageDisk();
        $storedPath = $file->store('documents/project/'.$project->id, $disk);

        $document = Document::query()->create([
            'title' => (string) ($attributes['title'] ?? pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)),
            'description' => $attributes['description'] ?? null,
            'original_name' => $file->getClientOriginalName(),
            'stored_name' => basename((string) $storedPath),
            'extension' => $file->getClientOriginalExtension(),
            'mime_type' => (string) $file->getClientMimeType(),
            'file_size' => (int) $file->getSize(),
            'storage_disk' => $disk,
            'storage_path' => $storedPath,
            'owner_scope' => Document::OWNER_SCOPE_PROJECT,
            'visibility' => Document::VISIBILITY_PROJECT,
            'replace_mode' => $this->replaceBehavior(),
            'uploaded_by_id' => $actor->id,
        ]);

        $document->ownerProjects()->sync([$project->id]);

        return $document->fresh();
    }

    public function replaceFile(Document $document, UploadedFile $file, ?User $actor = null): Document
    {
        $disk = $this->storageDisk();
        $folder = $document->isProjectOwned()
            ? 'documents/project/'.($document->ownerProjects()->value('projects.id') ?? 'unknown')
            : 'documents/user/'.($document->ownerUsers()->value('users.id') ?? 'unknown');

        $oldPath = $document->storage_path;
        $storedPath = $file->store($folder, $disk);

        $document->fill([
            'original_name' => $file->getClientOriginalName(),
            'stored_name' => basename((string) $storedPath),
            'extension' => $file->getClientOriginalExtension(),
            'mime_type' => (string) $file->getClientMimeType(),
            'file_size' => (int) $file->getSize(),
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
        $rawAllowedTypes = (string) setting('documents.allowed_types', 'pdf,doc,docx,jpg,jpeg,png');
        $allowedExtensions = collect(explode(',', $rawAllowedTypes))
            ->map(fn (string $extension): string => trim(strtolower($extension)))
            ->filter()
            ->values()
            ->all();

        return [
            'max_kilobytes' => (int) setting('documents.max_file_size', 10240),
            'allowed_extensions' => $allowedExtensions,
        ];
    }

    private function storageDisk(): string
    {
        return (string) setting('documents.storage_disk', 'local');
    }

    private function replaceBehavior(): string
    {
        $replaceBehavior = (string) setting('documents.replace_behavior', Document::REPLACE_MODE_REPLACE);

        return in_array($replaceBehavior, [Document::REPLACE_MODE_REPLACE, Document::REPLACE_MODE_KEEP_HISTORY], true)
            ? $replaceBehavior
            : Document::REPLACE_MODE_REPLACE;
    }
}
