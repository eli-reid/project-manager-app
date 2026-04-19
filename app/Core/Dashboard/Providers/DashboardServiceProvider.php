<?php

namespace App\Core\Dashboard\Providers;

use App\Core\Dashboard\Services\DashboardWidgetRegistry;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View as ViewInstance;

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
        View::composer('dashboard', function (ViewInstance $view): void {
            /** @var DashboardWidgetRegistry $registry */
            $registry = $this->app->make(DashboardWidgetRegistry::class);

            $allWidgets = collect($registry->all())
                ->filter(fn (array $widget): bool => $widget['ability'] === '' || Gate::allows($widget['ability']));

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
        });
    }
}
