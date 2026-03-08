<?php

namespace App\Core\Scheduler\Policies;

use App\Core\Scheduler\Models\ScheduledTask;
use App\Core\User\Models\User;

class ScheduledTaskPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('scheduler.view');
    }

    public function view(User $user, ScheduledTask $scheduledTask): bool
    {
        return $user->hasPermission('scheduler.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('scheduler.create');
    }

    public function update(User $user, ScheduledTask $scheduledTask): bool
    {
        return $user->hasPermission('scheduler.edit');
    }

    public function delete(User $user, ScheduledTask $scheduledTask): bool
    {
        return $user->hasPermission('scheduler.delete');
    }

    public function toggle(User $user, ScheduledTask $scheduledTask): bool
    {
        return $user->hasPermission('scheduler.toggle');
    }

    public function run(User $user, ScheduledTask $scheduledTask): bool
    {
        return $user->hasPermission('scheduler.run');
    }
}
