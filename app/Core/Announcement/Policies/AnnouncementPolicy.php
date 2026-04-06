<?php

namespace App\Core\Announcement\Policies;

use App\Core\Announcement\Models\Announcement;
use App\Core\Identity\Models\User;

class AnnouncementPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('announcements.view');
    }

    public function view(User $user, Announcement $announcement): bool
    {
        return $user->hasPermission('announcements.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('announcements.create');
    }

    public function update(User $user, Announcement $announcement): bool
    {
        return $user->hasPermission('announcements.edit');
    }

    public function delete(User $user, Announcement $announcement): bool
    {
        return $user->hasPermission('announcements.delete');
    }

    public function manage(User $user): bool
    {
        return $this->viewAny($user);
    }
}
