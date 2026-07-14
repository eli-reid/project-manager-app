<?php

namespace App\Core\Dashboard\Livewire\Concerns;

use App\Core\Dashboard\Enums\DashboardSection;
use App\Core\Dashboard\Services\DashboardWidgetRegistry;
use App\Support\Diagnostics\MemoryProbe;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Throwable;

trait ResolvesDashboardSections
{
    /**
     * Resolve dashboard sections and emit debug-only memory probe logs.
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    protected function resolveDashboardSectionsWithMemoryProbe(DashboardWidgetRegistry $widgetRegistry, string $viewName): array
    {
        if (! $this->dashboardMemoryProbeEnabled()) {
            return $this->resolveDashboardSections($widgetRegistry, $viewName);
        }

        $baseline = MemoryProbe::snapshot('dashboard.resolve.start');
        $sections = $this->resolveDashboardSections($widgetRegistry, $viewName);
        $context = $this->dashboardMemoryProbeContext($sections, $widgetRegistry);

        $this->logDashboardMemoryProbe($viewName, 'sections_resolved', $baseline, $context);

        app()->terminating(function () use ($baseline, $context, $viewName): void {
            $this->logDashboardMemoryProbe($viewName, 'request_terminated', $baseline, $context);
        });

        return $sections;
    }

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
            $enumOrder = \array_map(fn (DashboardSection $c): string => $c->value, DashboardSection::cases());

            $orderedSections = \array_merge(
                \array_values(\array_intersect($enumOrder, $availableSections)),
                \array_values(\array_diff($availableSections, $enumOrder)),
            );

            return collect($orderedSections)
                ->mapWithKeys(fn (string $section): array => [
                    $section => $allWidgets
                        ->filter(fn (array $widget): bool => ($widget['section'] ?? '') === $section)
                        ->sortBy([['sort', 'asc'], ['title', 'asc']])
                        ->values()
                        ->all(),
                ])
                ->filter(fn (array $widgets): bool => \count($widgets) > 0)
                ->all();
        } catch (Throwable $exception) {
            Log::error('Dashboard sections could not be composed.', [
                'view' => $viewName,
                'exception' => $exception,
            ]);

            return [];
        }
    }

    protected function dashboardMemoryProbeEnabled(): bool
    {
        return (bool) config('app.debug', false);
    }

    /**
     * @param  array<string, array<int, array<string, mixed>>>  $sections
     * @return array<string, mixed>
     */
    protected function dashboardMemoryProbeContext(array $sections, DashboardWidgetRegistry $widgetRegistry): array
    {
        $allWidgets = $widgetRegistry->all();
        $sectionWidgetCounts = [];
        $sectionWidgetKeys = [];

        foreach ($sections as $section => $widgets) {
            $sectionWidgetCounts[$section] = \count($widgets);
            $sectionWidgetKeys[$section] = \array_values(\array_map(
                static fn (array $widget): string => (string) ($widget['key'] ?? 'unknown'),
                $widgets,
            ));
        }

        return [
            'section_count' => \count($sections),
            'widget_count' => \array_sum($sectionWidgetCounts),
            'registry_definition_count' => \count($allWidgets),
            'registry_payload' => MemoryProbe::inspect($allWidgets, 'dashboard.registry'),
            'sections_payload' => MemoryProbe::inspect($sections, 'dashboard.sections'),
            'largest_sections' => MemoryProbe::largestItems($sections, 5),
            'section_widget_counts' => $sectionWidgetCounts,
            'section_widget_keys' => $sectionWidgetKeys,
        ];
    }

    /**
     * @param  array{label:string|null,current_bytes:int,current_mb:float,real_bytes:int,real_mb:float,peak_bytes:int,peak_mb:float}  $baseline
     * @param  array<string, mixed>  $context
     */
    protected function logDashboardMemoryProbe(string $viewName, string $phase, array $baseline, array $context): void
    {
        MemoryProbe::logDelta('Dashboard memory probe.', $baseline, $phase, [
            'view' => $viewName,
            'phase' => $phase,
            ...$context,
        ]);
    }
}
