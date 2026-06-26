<?php

namespace App\Domain\Auth\Services;

use App\Core\Identity\Models\User;

class PasswordSync
{
    protected static array $handlers = [];

    public static function registerHandler(callable $handler): void
    {
        static::$handlers[] = $handler;
    }

    public static function sync(User $user, string $plaintextPassword): void
    {
        foreach (static::$handlers as $handler) {
            $handler($user, $plaintextPassword);
        }
    }
}
