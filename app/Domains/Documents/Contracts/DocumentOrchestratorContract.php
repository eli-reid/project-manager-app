<?php

namespace App\Domains\Documents\Contracts;

use App\Core\Identity\Models\User;
use App\Domains\Documents\Models\Document;
use App\Domains\Projects\Models\Project;
use Illuminate\Http\UploadedFile;

interface DocumentOrchestratorContract
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function uploadUserDocument(User $owner, UploadedFile $file, array $attributes = []): Document;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function uploadProjectDocument(Project $project, User $actor, UploadedFile $file, array $attributes = []): Document;

    public function replaceFile(Document $document, UploadedFile $file, ?User $actor = null, ?string $folderPath = null): Document;

    public function moveDocument(Document $document, ?string $folderPath = null): Document;

    public function deleteDocument(Document $document): void;

    /**
     * @return array{max_kilobytes:int, allowed_extensions:array<int, string>}
     */
    public function validationRules(): array;
}
