<?php

namespace App\Core\Queue\Policies;

use App\Core\Identity\Models\User;

class QueuePolicy
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
        return $user->isAdmin() || $user->hasPermission('queue.view');
    }

    public function manage(User $user): bool
    {
        return $user->isAdmin() || $user->hasPermission('queue.manage');
    }
}
