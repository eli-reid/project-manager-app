<?php

namespace App\Core\Auth\User\Events;

use App\Core\Identity\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

class UserLoggedIn
{
    use Dispatchable;

    public function __construct(public readonly User $user,
        public readonly array $attributes = [],
        public readonly array $meta = []) {}
}
