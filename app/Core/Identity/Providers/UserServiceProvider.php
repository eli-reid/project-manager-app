<?php

namespace App\Core\Identity\Providers;

use App\Core\Identity\Contracts\PluginUserResolver;
use App\Core\Identity\Livewire\Auth\User\DesktopUserMenu;
use App\Core\Identity\Livewire\Auth\User\MobileUserMenu;
use App\Core\Identity\Services\PluginUserResolverService;
use App\Providers\Concerns\RegistersMobileRedirectMappings;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class UserServiceProvider extends ServiceProvider
{
    use RegistersMobileRedirectMappings;

    public function register(): void
    {
        $this->app->singleton(PluginUserResolver::class, PluginUserResolverService::class);
    }

    public function boot(): void
    {
        $this->registerMobileExactRouteMapping('profile.edit', 'settings.mobile.profile');
        $this->registerMobileExactRouteMapping('user-password.edit', 'settings.mobile.password');
        $this->registerMobileExactRouteMapping('two-factor.show', 'settings.mobile.two-factor');
        $this->registerMobileExactRouteMapping('notifications.edit', 'settings.mobile.notifications');
        $this->registerMobileExactRouteMapping('appearance.edit', 'settings.mobile.appearance');

        $this->registerInfrastructure();
        $this->registerUIComponents();
        $this->registerRoutes();
    }

    private function registerInfrastructure(): void
    {
        $this->loadViewsFrom(__DIR__.'/../Resources/Views', 'core-user');
    }

    private function registerUIComponents(): void
    {
        Livewire::addNamespace('settings', classNamespace: 'App\Core\Identity\Livewire\Settings');
        Livewire::component('auth.user.desktop-user-menu', DesktopUserMenu::class);
        Livewire::component('auth.user.mobile-user-menu', MobileUserMenu::class);
    }

    private function registerRoutes(): void
    {
        Route::middleware(['web'])
            ->group(__DIR__.'/../Routes/settings.php');

        Route::middleware(['web'])
            ->group(__DIR__.'/../Routes/mobile.php');
    }
}
