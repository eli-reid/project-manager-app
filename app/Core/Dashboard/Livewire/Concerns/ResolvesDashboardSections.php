<?php

namespace App\Core\Dashboard\Livewire\Concerns;

use App\Core\Dashboard\Enums\DashboardSection;
use App\Core\Dashboard\Services\DashboardWidgetRegistry;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Throwable;

trait ResolvesDashboardSections
{
    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    protected function resolveDashboardSections(DashboardWidgetRegistry $widgetRegistry, string $viewName): array
    {
        try {
            $allWidgets = collect($widgetRegistry->all())
                ->filter(function (array $widget): bool {
                    if (($widget['ability'] ?? '') === '') {
                        return true;
                    }

                    try {
                        $model = $widget['ability_model'] ?? '';

                        return $model !== ''
                            ? Gate::allows($widget['ability'], $model)
                            : Gate::allows($widget['ability']);
                    } catch (Throwable $exception) {
                        Log::warning('Dashboard widget authorization check failed.', [
                            'widget_key' => $widget['key'] ?? null,
                            'ability' => $widget['ability'] ?? null,
                            'ability_model' => $widget['ability_model'] ?? null,
                            'exception' => $exception,
                        ]);

                        return false;
                    }
                });

            // Determine available sections from widgets (preserve widget-defined order)
            $availableSections = $allWidgets->pluck('section')
                ->filter()
                ->unique()
                ->values()
                ->all();

            // Use enum-defined core order first, then append any extra widget-defined sections
            $enumOrder = array_map(fn (DashboardSection $c): string => $c->value, DashboardSection::cases());

            $orderedSections = array_merge(
                array_values(array_intersect($enumOrder, $availableSections)),
                array_values(array_diff($availableSections, $enumOrder)),
            );

            return collect($orderedSections)
                ->mapWithKeys(fn (string $section): array => [
                    $section => $allWidgets
                        ->filter(fn (array $widget): bool => ($widget['section'] ?? '') === $section)
                        ->sortBy([['sort', 'asc'], ['title', 'asc']])
                        ->values()
                        ->all(),
                ])
                ->filter(fn (array $widgets): bool => count($widgets) > 0)
                ->all();
        } catch (Throwable $exception) {
            Log::error('Dashboard sections could not be composed.', [
                'view' => $viewName,
                'exception' => $exception,
            ]);

            return [];
        }
    }
}
