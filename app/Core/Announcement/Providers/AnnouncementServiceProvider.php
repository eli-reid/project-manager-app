<?php

namespace App\Core\Announcement\Providers;

use App\Core\Announcement\Models\Announcement;
use App\Core\Announcement\Permissions\AnnouncementPermissions;
use App\Core\Announcement\Policies\AnnouncementPolicy;
use App\Core\Auth\Permission\Contracts\PermissionRegistryContract;
use App\Core\Dashboard\Data\WidgetDefinition;
use App\Core\Dashboard\Services\DashboardWidgetRegistry;
use App\Providers\Concerns\RegistersMobileRedirectMappings;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AnnouncementServiceProvider extends ServiceProvider
{
    use RegistersMobileRedirectMappings;

    public function register(): void
    {
        //
    }

    public function boot(DashboardWidgetRegistry $widgetRegistry): void
    {
        $this->registerMobileRoutePrefixMapping('announcements.', 'mobile.announcements.');

        $this->registerAuthorization();
        $this->registerPermissions();
        $this->registerInfrastructure();
        $this->registerUIComponents();
        $this->registerDashboardWidgets($widgetRegistry);
        $this->registerRoutes();
    }

    private function registerDashboardWidgets(DashboardWidgetRegistry $widgetRegistry): void
    {
        $widgetRegistry->registerDefinitions([
            new WidgetDefinition(
                key: 'core.announcements',
                component: 'announcement::dashboard.widget',
                section: 'primary',
                sort: 10,
                span: 'half',
                ability: 'announcement.view',
                title: 'Company Announcements',
                description: 'Latest updates for the team.',
            ),
        ]);
    }

    private function registerUIComponents(): void
    {
        Livewire::addNamespace('announcement', classNamespace: 'App\Core\Announcement\Livewire');
    }

    private function registerAuthorization(): void
    {
        Gate::policy(Announcement::class, AnnouncementPolicy::class);
    }

    private function registerPermissions(): void
    {
        /** @var PermissionRegistryContract $registry */
        $registry = $this->app->make(PermissionRegistryContract::class);
        $registry->registerPermissions(AnnouncementPermissions::all());
    }

    private function registerInfrastructure(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        $this->loadViewsFrom(__DIR__.'/../Resources/Views', 'announcement');

    }

    private function registerRoutes(): void
    {
        Route::prefix('admin')
            ->name('admin.')
            ->middleware(['web', 'auth'])
            ->group(__DIR__.'/../Routes/admin.php');

        Route::middleware(['web', 'auth'])
            ->group(__DIR__.'/../Routes/web.php');

        Route::middleware(['mobile', 'auth'])
            ->group(__DIR__.'/../Routes/mobile.php');

        Route::prefix('api')
            ->middleware(['api', 'auth'])
            ->group(__DIR__.'/../Routes/api.php');
    }
}
