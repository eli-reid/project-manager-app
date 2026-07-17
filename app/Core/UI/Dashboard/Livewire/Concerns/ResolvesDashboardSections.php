<?php

namespace App\Core\UI\Dashboard\Livewire\Concerns;

use App\Core\UI\Dashboard\Services\DashboardWidgetRegistry;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Throwable;

trait ResolvesDashboardSections
{
    /**
     * @var list<string>
     */
    private const SECTION_ORDER = ['primary', 'personal', 'operations', 'alerts', 'admin'];

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

            return collect(self::SECTION_ORDER)
                ->mapWithKeys(fn (string $section): array => [
                    $section => $allWidgets
                        ->filter(fn (array $widget): bool => $widget['section'] === $section)
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
