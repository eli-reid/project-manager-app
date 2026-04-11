<?php

namespace App\Domains\Reports\Services;

use Illuminate\Support\Facades\Log;

class ReportRegistry
{
    /**
     * @var array<string, array{key:string,section:string,title:string,description:string,route:string,badge_label:string,badge_color:string,sort:int}>
     */
    private array $definitions = [];

    /**
     * @param  array<int, array{key:string,section?:string,title?:string,description?:string,route?:string,badge_label?:string,badge_color?:string,sort?:int}>  $definitions
     */
    public function registerDefinitions(array $definitions): void
    {
        foreach ($definitions as $definition) {
            $key = (string) ($definition['key'] ?? '');
            $route = (string) ($definition['route'] ?? '');

            if ($key === '' || $route === '') {
                continue;
            }

            if (array_key_exists($key, $this->definitions)) {
                Log::warning('ReportRegistry: duplicate key ignored during registerDefinitions.', [
                    'key' => $key,
                    'existing_title' => $this->definitions[$key]['title'],
                ]);

                continue;
            }

            $this->definitions[$key] = [
                'key' => $key,
                'section' => (string) ($definition['section'] ?? 'general'),
                'title' => (string) ($definition['title'] ?? str($key)->replace(['.', '-', '_'], ' ')->headline()->value()),
                'description' => (string) ($definition['description'] ?? ''),
                'route' => $route,
                'badge_label' => (string) ($definition['badge_label'] ?? 'Available'),
                'badge_color' => (string) ($definition['badge_color'] ?? 'green'),
                'sort' => (int) ($definition['sort'] ?? 100),
            ];
        }
    }

    /**
     * @return array<int, array{key:string,section:string,title:string,description:string,route:string,badge_label:string,badge_color:string,sort:int}>
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
}
