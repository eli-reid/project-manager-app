<?php

namespace App\Core\Scheduler\Services;

class TaskTypeRegistry
{
    /**
     * Provider-level registrations should only describe the task itself.
     * Scheduling fields are owned by scheduler admin configuration.
     *
     * @var array<int, string>
     */
    protected array $supportedDefinitionKeys = [
        'name',
        'description',
        'task_config',
    ];

    /**
     * @var array<string, class-string|object>
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
    public function register(string $featureType, mixed $class, array $definition = []): void
    {
        $this->types[$featureType] = $class;

        $normalizedDefinition = [];
        foreach ($this->supportedDefinitionKeys as $key) {
            if (array_key_exists($key, $definition)) {
                $normalizedDefinition[$key] = $definition[$key];
            }
        }

        $this->definitions[$featureType] = [
            'name' => $normalizedDefinition['name'] ?? str($featureType)->replace('_', ' ')->headline()->value(),
            'description' => $normalizedDefinition['description'] ?? '',
            'task_config' => is_array($normalizedDefinition['task_config'] ?? null) ? $normalizedDefinition['task_config'] : [],
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
