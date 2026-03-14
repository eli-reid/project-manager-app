<?php

namespace App\Domains\Addresses\Policies;

use App\Core\User\Models\User;
use App\Domains\Addresses\Models\Address;

class AddressPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('addresses.view');
    }

    public function view(User $user, Address $address): bool
    {
        return $user->hasPermission('addresses.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('addresses.create');
    }

    public function update(User $user, Address $address): bool
    {
        return $user->hasPermission('addresses.edit');
    }

    public function delete(User $user, Address $address): bool
    {
        return $user->hasPermission('addresses.delete');
    }
}
