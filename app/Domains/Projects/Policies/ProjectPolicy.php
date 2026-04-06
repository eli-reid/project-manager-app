<?php

namespace App\Domains\Projects\Policies;

use App\Core\Identity\Models\User;
use App\Domains\Projects\Models\Project;

class ProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('projects.view');
    }

    public function view(User $user, Project $project): bool
    {
        return $user->hasPermission('projects.view');
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
