<?php

namespace App\Core\Dashboard\Services;

use App\Core\Dashboard\Data\WidgetDefinition;
use Illuminate\Support\Facades\Log;

class DashboardWidgetRegistry
{
    /**
     * @var array<string, array{key: string, component: string, section: string, sort: int, span: string, ability: string, ability_model: string, title: string, description: string}>
     */
    private array $definitions = [];

    /**
     * @param  array<int, WidgetDefinition>  $definitions
     */
    public function registerDefinitions(array $definitions): void
    {
        foreach ($definitions as $definition) {
            if (! $definition instanceof WidgetDefinition) {
                continue;
            }

            if ($definition->key === '' || $definition->component === '') {
                continue;
            }

            if (array_key_exists($definition->key, $this->definitions)) {
                Log::warning('DashboardWidgetRegistry: duplicate key ignored during registerDefinitions.', [
                    'key' => $definition->key,
                    'existing_title' => $this->definitions[$definition->key]['title'],
                ]);

                continue;
            }

            $this->definitions[$definition->key] = $definition->toArray();
        }
    }

    /**
     * @return array<int, array{key: string, component: string, section: string, sort: int, span: string, ability: string, ability_model: string, title: string, description: string}>
     */
    public function forSection(string $section): array
    {
        return collect($this->definitions)
            ->filter(fn (array $definition): bool => $definition['section'] === $section)
            ->sortBy([
                ['sort', 'asc'],
                ['title', 'asc'],
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{key: string, component: string, section: string, sort: int, span: string, ability: string, ability_model: string, title: string, description: string}>
     */
    public function all(): array
    {
        return collect($this->definitions)
            ->sortBy([
                ['section', 'asc'],
                ['sort', 'asc'],
                ['title', 'asc'],
            ])
            ->values()
            ->all();
    }
}
