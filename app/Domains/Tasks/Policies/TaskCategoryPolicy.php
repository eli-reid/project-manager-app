<?php

namespace App\Domains\Tasks\Policies;

use App\Core\User\Models\User;
use App\Domains\Tasks\Models\TaskCategory;

class TaskCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('task-categories.view');
    }

    public function view(User $user, TaskCategory $taskCategory): bool
    {
        return $user->hasPermission('task-categories.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('task-categories.create');
    }

    public function update(User $user, TaskCategory $taskCategory): bool
    {
        return $user->hasPermission('task-categories.edit');
    }

    public function delete(User $user, TaskCategory $taskCategory): bool
    {
        return $user->hasPermission('task-categories.delete');
    }
}
