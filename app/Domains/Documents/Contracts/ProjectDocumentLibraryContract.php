<?php

namespace App\Domains\Documents\Contracts;

use App\Domains\Documents\Models\Document;
use Illuminate\Support\Collection;

interface ProjectDocumentLibraryContract
{
    public function listProjectOwned(string $projectId, ?string $search = null): Collection;

    public function listProjectAccessible(string $projectId, ?string $search = null): Collection;

    public function countProjectAccessible(string $projectId): int;

    public function findProjectOwnedOrFail(string $projectId, string $documentId): Document;

    /**
     * @param  array<int, string>  $candidateDocumentIds
     * @return array<int, string>
     */
    public function allowedDocumentIdsForProject(string $projectId, array $candidateDocumentIds): array;

    /**
     * @return array<int, string>
     */
    public function folderPathsForProject(string $projectId, bool $includeShared = false): array;
}
