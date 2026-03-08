<?php

namespace App\Core\Settings\Policies;

use App\Core\Settings\Models\SettingsSqlite;
use App\Core\User\Models\User;

class SettingPolicy
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
        return $user->hasPermission('settings.view');
    }

    public function view(User $user, SettingsSqlite $setting): bool
    {
        return $user->hasPermission('settings.view');
    }

    public function update(User $user): bool
    {
        return $user->hasPermission('settings.edit');
    }

    public function import(User $user): bool
    {
        return $user->hasPermission('settings.import');
    }
}
