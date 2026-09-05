<?php

namespace App\Domains\Addresses\Policies;

use App\Core\Identity\Models\User;
use App\Domains\Addresses\Models\Address;
use App\Domains\Addresses\Services\AddressAccessService;

class AddressPolicy
{
    public function viewAny(User $user): bool
    {
        return app(AddressAccessService::class)->canViewAny($user);
    }

    public function view(User $user, Address $address): bool
    {
        return app(AddressAccessService::class)->canAccessAddress($user, $address);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('addresses.create');
    }

    public function update(User $user, Address $address): bool
    {
        return $user->hasPermission('addresses.edit')
            && app(AddressAccessService::class)->canAccessAddress($user, $address);
    }

    public function delete(User $user, Address $address): bool
    {
        return $user->hasPermission('addresses.delete')
            && app(AddressAccessService::class)->canAccessAddress($user, $address);
    }
}
