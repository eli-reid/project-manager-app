<?php

namespace App\Domains\Documents\Services;

use App\Domains\Documents\Contracts\ProjectDocumentLibraryContract;
use App\Domains\Documents\Models\Document;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;

class ProjectDocumentLibrary implements ProjectDocumentLibraryContract
{
    public function listProjectOwned(string $projectId, ?string $search = null): Collection
    {
        $query = Document::query()
            ->projectOwned()
            ->ownedByProject($projectId)
            ->with('uploadedBy:id,first_name,last_name')
            ->orderByRaw("COALESCE(folder_path, '')")
            ->orderBy('title');

        $this->applySearch($query, $search);

        return $query->get();
    }

    public function listProjectAccessible(string $projectId, ?string $search = null): Collection
    {
        $query = Document::query()
            ->where(function (Builder $query) use ($projectId): void {
                $query->ownedByProject($projectId)
                    ->orWhere(fn (Builder $sharedQuery): Builder => $sharedQuery->sharedWithProject($projectId));
            })
            ->orderByRaw("COALESCE(folder_path, '')")
            ->orderBy('title');

        $this->applySearch($query, $search);

        return $query->get();
    }

    public function countProjectAccessible(string $projectId): int
    {
        return Document::query()
            ->where(function (Builder $query) use ($projectId): void {
                $query->ownedByProject($projectId)
                    ->orWhere(fn (Builder $sharedQuery): Builder => $sharedQuery->sharedWithProject($projectId));
            })
            ->count();
    }

    public function findProjectOwnedOrFail(string $projectId, string $documentId): Document
    {
        $document = Document::query()
            ->projectOwned()
            ->ownedByProject($projectId)
            ->find($documentId);

        if (! $document instanceof Document) {
            throw (new ModelNotFoundException)->setModel(Document::class, [$documentId]);
        }

        return $document;
    }

    public function allowedDocumentIdsForProject(string $projectId, array $candidateDocumentIds): array
    {
        if ($candidateDocumentIds === []) {
            return [];
        }

        return Document::query()
            ->where(function (Builder $query) use ($projectId): void {
                $query->ownedByProject($projectId)
                    ->orWhere(fn (Builder $sharedQuery): Builder => $sharedQuery->sharedWithProject($projectId));
            })
            ->whereIn('id', $candidateDocumentIds)
            ->pluck('id')
            ->values()
            ->all();
    }

    public function folderPathsForProject(string $projectId, bool $includeShared = false): array
    {
        $query = Document::query()
            ->select('folder_path')
            ->whereNotNull('folder_path')
            ->where('folder_path', '!=', '');

        if ($includeShared) {
            $query->where(function (Builder $query) use ($projectId): void {
                $query->ownedByProject($projectId)
                    ->orWhere(fn (Builder $sharedQuery): Builder => $sharedQuery->sharedWithProject($projectId));
            });
        } else {
            $query->ownedByProject($projectId);
        }

        return $query
            ->distinct()
            ->orderBy('folder_path')
            ->pluck('folder_path')
            ->filter(static fn (mixed $value): bool => is_string($value) && $value !== '')
            ->values()
            ->all();
    }

    private function applySearch(Builder $query, ?string $search): void
    {
        if (! is_string($search) || $search === '') {
            return;
        }

        $query->where(function (Builder $query) use ($search): void {
            $query->where('title', 'like', '%'.$search.'%')
                ->orWhere('original_name', 'like', '%'.$search.'%');
        });
    }
}
