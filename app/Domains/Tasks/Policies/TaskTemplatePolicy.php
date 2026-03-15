<?php

namespace App\Domains\Tasks\Policies;

use App\Core\User\Models\User;
use App\Domains\Tasks\Models\TaskTemplate;

class TaskTemplatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('task-templates.view');
    }

    public function view(User $user, TaskTemplate $taskTemplate): bool
    {
        return $user->hasPermission('task-templates.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('task-templates.create');
    }

    public function update(User $user, TaskTemplate $taskTemplate): bool
    {
        return $user->hasPermission('task-templates.edit');
    }

    public function delete(User $user, TaskTemplate $taskTemplate): bool
    {
        return $user->hasPermission('task-templates.delete');
    }
}
