<?php

namespace App\Domains\Projects\Policies;

use App\Core\Identity\Models\User;
use App\Domains\Projects\Models\Project;
use App\Domains\Projects\Services\ProjectAccessService;

class ProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('projects.view');
    }

    public function view(User $user, Project $project): bool
    {
        if (! $user->hasPermission('projects.view')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        $projectAccessService = app(ProjectAccessService::class);

        if (! $projectAccessService->projectUsesScopedAccess($project)) {
            return true;
        }

        return $projectAccessService->hasAccess($project, $user);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('projects.create');
    }

    public function update(User $user, Project $project): bool
    {
        return $user->hasPermission('projects.edit');
    }

    public function delete(User $user, Project $project): bool
    {
        return $user->hasPermission('projects.delete');
    }
}
