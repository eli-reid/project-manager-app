<?php

namespace App\Core\Auth\Role\Policies;

use App\Core\Auth\Role\Models\Role;
use App\Core\User\Models\User;

class RolePolicy
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
        return $user->hasPermission('roles.view');
    }

    public function view(User $user, Role $role): bool
    {
        return $user->hasPermission('roles.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('roles.create');
    }

    public function update(User $user, Role $role): bool
    {
        return $user->hasPermission('roles.edit');
    }

    public function delete(User $user, Role $role): bool
    {
        return $user->hasPermission('roles.delete') && ! $role->isBuiltIn();
    }

    public function assignPermissions(User $user, Role $role): bool
    {
        return $user->hasPermission('roles.assign-permissions') || $user->hasPermission('roles.edit');
    }

    public function assignUsers(User $user, Role $role): bool
    {
        return $user->hasPermission('roles.edit');
    }
}
