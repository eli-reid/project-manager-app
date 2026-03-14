<?php

namespace App\Domains\Clients\Policies;

use App\Core\User\Models\User;
use App\Domains\Clients\Models\Client;

class ClientPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('clients.view');
    }

    public function view(User $user, Client $client): bool
    {
        return $user->hasPermission('clients.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('clients.create');
    }

    public function update(User $user, Client $client): bool
    {
        return $user->hasPermission('clients.edit');
    }

    public function delete(User $user, Client $client): bool
    {
        return $user->hasPermission('clients.delete');
    }
}
