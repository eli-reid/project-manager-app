<?php

namespace App\Core\Announcement\Providers;

use App\Core\Announcement\Models\Announcement;
use App\Core\Announcement\Permissions\AnnouncementPermissions;
use App\Core\Announcement\Policies\AnnouncementPolicy;
use App\Core\User\Services\PermissionRegistry;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AnnouncementServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->registerAuthorizationGates();
        $this->registerPermissions();
        $this->configureMigrations();
        $this->configureViews();
        $this->registerLivewireComponents();
        $this->configureRoutes();
    }

    private function registerLivewireComponents(): void
    {
        Livewire::component('app.core.announcement.livewire.admin.announcements', \App\Core\Announcement\Livewire\Admin\Announcements\Index::class);
        Livewire::component('app.core.announcement.livewire.admin.announcements.form', \App\Core\Announcement\Livewire\Admin\Announcements\Form::class);
        Livewire::component('app.core.announcement.livewire.dashboard.widget', \App\Core\Announcement\Livewire\Dashboard\Widget::class);
    }

    private function registerAuthorizationGates(): void
    {
        Gate::policy(Announcement::class, AnnouncementPolicy::class);
    }

    private function registerPermissions(): void
    {
        /** @var PermissionRegistry $registry */
        $registry = $this->app->make(PermissionRegistry::class);

        $registry->registerPermissions(array_map(function (array $definition): array {
            $resource = (string) $definition['resource'];
            $action = (string) $definition['action'];

            return [
                'resource' => $resource,
                'action' => $action,
                'label' => $definition['label'] ?? str($resource.' '.$action)->replace(['_', '-'], ' ')->headline()->value(),
                'description' => $definition['description'] ?? '',
            ];
        }, AnnouncementPermissions::all()));
    }

    private function configureMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
    }

    private function configureViews(): void
    {
        $this->loadViewsFrom(__DIR__.'/../Resources/Views', 'announcement');
    }

    private function configureRoutes(): void
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
            ->middleware(['api', 'auth:sanctum'])
            ->group(__DIR__.'/../Routes/api.php');
    }
}
