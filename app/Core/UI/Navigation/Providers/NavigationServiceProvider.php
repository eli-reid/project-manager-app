<?php

declare(strict_types=1);

namespace App\Core\Navigation\Providers;

use App\Core\Navigation\Services\NavigationManager;
use Illuminate\Support\ServiceProvider;

class NavigationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(NavigationManager::class, function () {
            return new NavigationManager;
        });

        // optional alias for convenience
        $this->app->alias(NavigationManager::class, 'navigation.manager');
    }

    public function boot(): void
    {
        // load package views so other packages/plugins can override them
        $this->loadViewsFrom(__DIR__.'/../Resources/Views', 'core-navigation');
    }
}
