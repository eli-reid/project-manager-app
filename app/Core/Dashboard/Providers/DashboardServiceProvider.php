<?php

namespace App\Core\Dashboard\Providers;

use App\Core\Dashboard\Services\DashboardWidgetRegistry;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View as ViewInstance;
use Throwable;

class DashboardServiceProvider extends ServiceProvider
{
    /**
     * @var list<string>
     */
    private const SECTION_ORDER = ['primary', 'personal', 'operations', 'alerts', 'admin'];

    public function register(): void
    {
        $this->app->singleton(DashboardWidgetRegistry::class);
    }

    public function boot(): void
    {
        View::composer(['dashboard', 'mobile.dashboard'], function (ViewInstance $view): void {
            try {
                /** @var DashboardWidgetRegistry $registry */
                $registry = $this->app->make(DashboardWidgetRegistry::class);

                $allWidgets = collect($registry->all())
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

                $sections = collect(self::SECTION_ORDER)
                    ->mapWithKeys(fn (string $section): array => [
                        $section => $allWidgets
                            ->filter(fn (array $w): bool => $w['section'] === $section)
                            ->sortBy([['sort', 'asc'], ['title', 'asc']])
                            ->values()
                            ->all(),
                    ])
                    ->filter(fn (array $widgets): bool => count($widgets) > 0)
                    ->all();

                $view->with('sections', $sections);
            } catch (Throwable $exception) {
                Log::error('Dashboard sections could not be composed.', [
                    'view' => $view->name(),
                    'exception' => $exception,
                ]);

                $view->with('sections', []);
            }
        });
    }
}
