<?php

namespace App\Core\Queue\Policies;

use App\Core\User\Models\User;

class QueuePolicy
{
    public function manage(User $user): bool
    {
        return $user->hasPermission('queue.manage');
    }

    public function view(User $user): bool
    {
        return $user->hasPermission('queue.view');
    }
}
