<?php

namespace App\Events;

use App\Core\Identity\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserRegistered
{
    use Dispatchable, SerializesModels;

    public User $user;

    /**
     * Arbitrary plugin payload (namespaced arrays like 'payroll' => [...])
     *
     * @var array<string, mixed>
     */
    public array $payload;

    public function __construct(User $user, array $payload = [])
    {
        $this->user = $user;
        $this->payload = $payload;
    }
}
