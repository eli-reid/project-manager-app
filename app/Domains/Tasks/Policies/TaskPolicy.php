<?php

namespace App\Domains\Tasks\Policies;

use App\Core\Identity\Models\User;
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

    public function updateStatus(User $user, Task $task): bool
    {
        return $user->hasPermission('tasks.edit') || $user->hasPermission('tasks.edit-status');
    }

    public function updatePriority(User $user, Task $task): bool
    {
        return $user->hasPermission('tasks.edit') || $user->hasPermission('tasks.edit-priority');
    }

    public function updateAssignee(User $user, Task $task): bool
    {
        return $user->hasPermission('tasks.edit') || $user->hasPermission('tasks.edit-assignee');
    }

    public function updateProgress(User $user, Task $task): bool
    {
        return $user->hasPermission('tasks.edit') || $user->hasPermission('tasks.edit-progress');
    }

    public function updateNotes(User $user, Task $task): bool
    {
        return $user->hasPermission('tasks.edit') || $user->hasPermission('tasks.edit-notes');
    }

    public function delete(User $user, Task $task): bool
    {
        return $user->hasPermission('tasks.delete');
    }
}
