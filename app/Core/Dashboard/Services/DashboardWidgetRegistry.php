<?php

namespace App\Core\Dashboard\Services;

use Illuminate\Support\Facades\Log;

class DashboardWidgetRegistry
{
    /**
     * @var array<string, array{key: string, component: string, section: string, sort: int, span: string, ability: string, title: string, description: string}>
     */
    private array $definitions = [];

    /**
     * @param  array<int, array{key: string, component: string, section?: string, sort?: int, span?: string, ability?: string, title?: string, description?: string}>  $definitions
     */
    public function registerDefinitions(array $definitions): void
    {
        foreach ($definitions as $definition) {
            $key = (string) ($definition['key'] ?? '');
            $component = (string) ($definition['component'] ?? '');

            if ($key === '' || $component === '') {
                continue;
            }

            if (array_key_exists($key, $this->definitions)) {
                Log::warning('DashboardWidgetRegistry: duplicate key ignored during registerDefinitions.', [
                    'key' => $key,
                    'existing_title' => $this->definitions[$key]['title'],
                ]);

                continue;
            }

            $this->definitions[$key] = [
                'key' => $key,
                'component' => $component,
                'section' => (string) ($definition['section'] ?? 'primary'),
                'sort' => (int) ($definition['sort'] ?? 100),
                'span' => (string) ($definition['span'] ?? 'third'),
                'ability' => (string) ($definition['ability'] ?? ''),
                'title' => (string) ($definition['title'] ?? str($key)->replace(['.', '-', '_'], ' ')->headline()->value()),
                'description' => (string) ($definition['description'] ?? ''),
            ];
        }
    }

    /**
     * @return array<int, array{key: string, component: string, section: string, sort: int, span: string, ability: string, title: string, description: string}>
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
     * @return array<int, array{key: string, component: string, section: string, sort: int, span: string, ability: string, title: string, description: string}>
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
