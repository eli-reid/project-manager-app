<?php

namespace App\Core\Scheduler\Services;

class TaskTypeRegistry
{
    /**
     * @var array<string, class-string>
     */
    protected array $types = [];

    /**
     * Register a task type → class mapping.
     */
    public function register(string $featureType, string $class): void
    {
        $this->types[$featureType] = $class;
    }

    /**
     * Resolve the class for a given feature type.
     */
    public function resolve(string $featureType): ?string
    {
        return $this->types[$featureType] ?? null;
    }

    /**
     * Optional: return all registered types (useful for debugging).
     */
    public function all(): array
    {
        return $this->types;
    }
}

