<?php

namespace App\Core\Scheduler\Services;

class TaskTypeRegistry
{
    /**
     * @var array<string, class-string>
     */
    protected array $types = [];

    /**
     * @var array<string, array<string, mixed>>
     */
    protected array $definitions = [];

    /**
     * Register a task type → class mapping.
     */
    /**
     * @param  array<string, mixed>  $definition
     */
    public function register(string $featureType, string $class, array $definition = []): void
    {
        $this->types[$featureType] = $class;

        $this->definitions[$featureType] = [
            'name' => $definition['name'] ?? str($featureType)->replace('_', ' ')->headline()->value(),
            'description' => $definition['description'] ?? '',
            'schedule_type' => $definition['schedule_type'] ?? 'daily',
            'time' => $definition['time'] ?? '09:00:00',
            'timezone' => $definition['timezone'] ?? 'America/New_York',
            'repeat_frequency' => $definition['repeat_frequency'] ?? 'once',
            'repeat_interval' => $definition['repeat_interval'] ?? 1,
            'is_active' => $definition['is_active'] ?? true,
            'is_enabled' => $definition['is_enabled'] ?? false,
            'task_config' => $definition['task_config'] ?? [],
            ...$definition,
        ];
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

    /**
     * @return array<string, array<string, mixed>>
     */
    public function definitions(): array
    {
        return $this->definitions;
    }
}
