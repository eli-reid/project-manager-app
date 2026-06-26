<?php

namespace App\Core\Identity\Services;

/**
 * Registry for runtime user relationship providers.
 * Domains can register a resolver callable that receives (User $user, ...$args)
 * and returns an Eloquent Relation instance (HasOne, HasMany, BelongsToMany, etc.).
 */
final class UserRelationshipRegistry
{
    /** @var array<string, callable> */
    private array $map = [];

    public function register(string $name, callable $resolver): void
    {
        $this->map[$name] = $resolver;
    }

    public function has(string $name): bool
    {
        return isset($this->map[$name]);
    }

    public function get(string $name): callable
    {
        return $this->map[$name];
    }

    public function unregister(string $name): void
    {
        unset($this->map[$name]);
    }
}
