<?php

namespace App\Domains\Documents\Policies;

use App\Core\Identity\Models\User;
use App\Domains\Documents\Models\Document;
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
            $project = $document->ownerProjects()->first();

            return $project !== null && $user->can('view', $project);
        }

        if ($document->visibility === Document::VISIBILITY_GLOBAL) {
            return true;
        }

        return $document->ownerUsers()->where('users.id', $user->id)->exists();
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
            $project = $document->ownerProjects()->first();

            return $project !== null
                && $user->hasPermission('documents.manage-project')
                && $user->can('view', $project);
        }

        return $document->ownerUsers()->where('users.id', $user->id)->exists();
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
            $project = $document->ownerProjects()->first();

            return $project !== null
                && $user->hasPermission('documents.manage-project')
                && $user->can('view', $project);
        }

        return $document->ownerUsers()->where('users.id', $user->id)->exists();
    }

    public function promoteToGlobal(User $user, Document $document): bool
    {
        return $document->isUserOwned()
            && $user->hasPermission('documents.promote-global')
            && $document->ownerUsers()->where('users.id', $user->id)->exists();
    }

    public function demoteToPrivate(User $user, Document $document): bool
    {
        return $document->isUserOwned()
            && $user->hasPermission('documents.demote-private')
            && $document->ownerUsers()->where('users.id', $user->id)->exists();
    }

    public function manageProjectDocuments(User $user, Project $project): bool
    {
        return $user->hasPermission('documents.manage-project')
            && $user->hasPermission('documents.view')
            && $user->can('view', $project);
    }

    public function attachToProject(User $user, Document $document, Project $project): bool
    {
        if (! $document->isProjectOwned()) {
            return false;
        }

        return $this->manageProjectDocuments($user, $project)
            && $this->update($user, $document);
    }

    public function detachFromProject(User $user, Document $document, Project $project): bool
    {
        if (! $document->isProjectOwned()) {
            return false;
        }

        return $this->manageProjectDocuments($user, $project)
            && $this->delete($user, $document);
    }
}
