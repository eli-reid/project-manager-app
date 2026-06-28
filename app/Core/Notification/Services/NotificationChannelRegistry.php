<?php

namespace App\Core\Notification\Services;

use App\Core\Notification\Contracts\NotificationChannel;
use Closure;
use InvalidArgumentException;

final class NotificationChannelRegistry
{
    /** @var array<string, string|callable> */
    private array $registry = [];

    /**
     * Register a channel factory or concrete class name.
     * The factory receives no arguments and should return a `NotificationChannel`.
     */
    public function register(string $name, string|callable $factory): void
    {
        $name = (string) $name;

        if ($name === '') {
            throw new InvalidArgumentException('Channel name cannot be empty.');
        }

        $this->registry[$name] = $factory;
    }

    public function has(string $name): bool
    {
        return array_key_exists($name, $this->registry);
    }

    /**
     * Resolve a channel instance.
     */
    public function resolve(string $name): NotificationChannel
    {
        if (! $this->has($name)) {
            throw new InvalidArgumentException(sprintf('Channel [%s] is not registered.', $name));
        }

        $factory = $this->registry[$name];

        if (is_string($factory)) {
            return app()->make($factory);
        }

        /** @var callable|Closure $factory */
        $instance = $factory();

        if (! $instance instanceof NotificationChannel) {
            throw new InvalidArgumentException(sprintf('Factory for channel [%s] did not return NotificationChannel.', $name));
        }

        return $instance;
    }

    /** @return array<int, string> */
    public function all(): array
    {
        return array_keys($this->registry);
    }
}
