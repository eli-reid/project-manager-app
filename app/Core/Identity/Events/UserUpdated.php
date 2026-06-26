<?php

namespace App\Core\Identity\Events;

use App\Core\Identity\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

class UserUpdated
{
    use Dispatchable;

    public function __construct(public readonly User $user,
        public readonly array $changes = [],
        public readonly array $meta = []) {}
}
