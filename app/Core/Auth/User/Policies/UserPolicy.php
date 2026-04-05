<?php

namespace App\Core\Auth\User\Policies;

use App\Core\User\Models\User;

class UserPolicy
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
        return $user->hasPermission('users.view');
    }

    public function view(User $user, User $target): bool
    {
        return $user->hasPermission('users.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('users.create');
    }

    public function update(User $user, User $target): bool
    {
        return $user->hasPermission('users.edit');
    }

    public function delete(User $user, User $target): bool
    {
        return $user->hasPermission('users.delete') && ! $target->is_built_in;
    }
}
