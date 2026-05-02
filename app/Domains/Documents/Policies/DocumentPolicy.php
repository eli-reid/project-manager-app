<?php

namespace App\Domains\Documents\Policies;

use App\Core\Identity\Models\User;
use App\Domains\Documents\Models\Document;
use App\Domains\Documents\Models\DocumentInternalShare;
use App\Domains\Projects\Models\Project;

class DocumentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('documents.view');
    }

    public function deleteAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function manageStorage(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, Document $document): bool
    {
        if (! $user->hasPermission('documents.view')) {
            return false;
        }

        if ($document->isProjectOwned()) {
            $project = Project::query()->find($document->owner_id);

            if ($project !== null && $user->can('view', $project)) {
                return true;
            }

            $sharedProjectIds = $document->internalShares()
                ->where('grantee_scope', DocumentInternalShare::GRANTEE_SCOPE_PROJECT)
                ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
                ->pluck('grantee_id');

            if ($sharedProjectIds->isEmpty()) {
                return false;
            }

            return Project::query()
                ->whereIn('id', $sharedProjectIds->all())
                ->get(['id'])
                ->contains(fn (Project $sharedProject): bool => $user->can('view', $sharedProject));
        }

        if ($document->visibility === Document::VISIBILITY_GLOBAL) {
            return true;
        }

        if ($document->owner_id === $user->id) {
            return true;
        }

        if (! Document::internalSharesTableExists()) {
            return false;
        }

        $hasDirectUserShare = $document->internalShares()
            ->where('grantee_scope', DocumentInternalShare::GRANTEE_SCOPE_USER)
            ->where('grantee_id', $user->id)
            ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->exists();

        if ($hasDirectUserShare) {
            return true;
        }

        return $this->hasActiveProjectShareForUser($document, $user);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('documents.create');
    }

    public function update(User $user, Document $document): bool
    {
        if (! $user->hasPermission('documents.update')) {
            return false;
        }

        if ($document->isProjectOwned()) {
            $project = Project::query()->find($document->owner_id);

            return $project !== null
                && $user->hasPermission('documents.manage-project')
                && $user->can('view', $project);
        }

        return $document->owner_id === $user->id;
    }

    public function delete(User $user, Document $document): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if (! $user->hasPermission('documents.delete')) {
            return false;
        }

        if ($document->isProjectOwned()) {
            $project = Project::query()->find($document->owner_id);

            return $project !== null
                && $user->hasPermission('documents.manage-project')
                && $user->can('view', $project);
        }

        return $document->owner_id === $user->id;
    }

    public function promoteToGlobal(User $user, Document $document): bool
    {
        return $document->isUserOwned()
            && $user->hasPermission('documents.promote-global')
            && $document->owner_id === $user->id;
    }

    public function demoteToPrivate(User $user, Document $document): bool
    {
        return $document->isUserOwned()
            && $user->hasPermission('documents.demote-private')
            && $document->owner_id === $user->id;
    }

    public function manageProjectDocuments(User $user, Project $project): bool
    {
        return $user->hasPermission('documents.manage-project')
            && $user->hasPermission('documents.view')
            && $user->can('view', $project);
    }

    public function attachToProject(User $user, Document $document, Project $project): bool
    {
        if ($document->isProjectOwned()) {
            return $this->manageProjectDocuments($user, $project)
                && $this->update($user, $document);
        }

        if (! Document::internalSharesTableExists()) {
            return false;
        }

        return $this->manageProjectDocuments($user, $project)
            && $document->internalShares()
                ->where('grantee_scope', DocumentInternalShare::GRANTEE_SCOPE_PROJECT)
                ->where('grantee_id', $project->id)
                ->where('permission_level', DocumentInternalShare::PERMISSION_ATTACH)
                ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
                ->exists();
    }

    public function detachFromProject(User $user, Document $document, Project $project): bool
    {
        if (! $document->isProjectOwned()) {
            return false;
        }

        return $this->manageProjectDocuments($user, $project)
            && $this->delete($user, $document);
    }

    public function share(User $user, Document $document): bool
    {
        if (! $user->hasPermission('documents.share')) {
            return false;
        }

        return $this->view($user, $document);
    }

    private function hasActiveProjectShareForUser(Document $document, User $user): bool
    {
        if (! Document::internalSharesTableExists()) {
            return false;
        }

        $sharedProjectIds = $document->internalShares()
            ->where('grantee_scope', DocumentInternalShare::GRANTEE_SCOPE_PROJECT)
            ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->pluck('grantee_id');

        if ($sharedProjectIds->isEmpty()) {
            return false;
        }

        return Project::query()
            ->whereIn('id', $sharedProjectIds->all())
            ->get(['id'])
            ->contains(fn (Project $sharedProject): bool => $user->can('view', $sharedProject));
    }
}
