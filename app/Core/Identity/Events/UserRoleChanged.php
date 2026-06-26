<?php

namespace App\Core\Identity\Events;

use App\Core\Identity\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

class UserRoleChanged
{
    use Dispatchable;

    public function __construct(public readonly User $user,
        public readonly array $roles = [],
        public readonly array $meta = []) {}
}
