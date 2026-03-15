<?php

namespace App\Domains\Tasks\Policies;

use App\Core\User\Models\User;
use App\Domains\Tasks\Models\Task;

class TaskPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('tasks.view');
    }

    public function view(User $user, Task $task): bool
    {
        return $user->hasPermission('tasks.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('tasks.create');
    }

    public function update(User $user, Task $task): bool
    {
        return $user->hasPermission('tasks.edit');
    }

    public function delete(User $user, Task $task): bool
    {
        return $user->hasPermission('tasks.delete');
    }
}
