<?php

namespace App\Core\PluginSystem\Policies;

use App\Core\Identity\Models\User;
use App\Core\PluginSystem\Models\InstalledPlugin;

class InstalledPluginPolicy
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
        return $user->hasPermission('plugins.view');
    }

    public function view(User $user, InstalledPlugin $installedPlugin): bool
    {
        return $user->hasPermission('plugins.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('plugins.install');
    }

    public function update(User $user, InstalledPlugin $installedPlugin): bool
    {
        return $user->hasPermission('plugins.review');
    }

    public function delete(User $user, InstalledPlugin $installedPlugin): bool
    {
        return $user->hasPermission('plugins.delete');
    }

    public function enable(User $user, InstalledPlugin $installedPlugin): bool
    {
        return $user->hasPermission('plugins.enable');
    }

    public function disable(User $user, InstalledPlugin $installedPlugin): bool
    {
        return $user->hasPermission('plugins.disable');
    }

    public function review(User $user, InstalledPlugin $installedPlugin): bool
    {
        return $user->hasPermission('plugins.review');
    }
}
