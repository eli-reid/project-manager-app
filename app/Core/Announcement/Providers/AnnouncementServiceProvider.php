<?php

namespace App\Core\Announcement\Providers;

use App\Core\Announcement\Livewire\Admin\Announcements\Form;
use App\Core\Announcement\Livewire\Admin\Announcements\Index;
use App\Core\Announcement\Livewire\Dashboard\Widget;
use App\Core\Announcement\Models\Announcement;
use App\Core\Announcement\Permissions\AnnouncementPermissions;
use App\Core\Announcement\Policies\AnnouncementPolicy;
use App\Core\Auth\Permission\Contracts\PermissionRegistryContract;
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
        $this->registerAuthorization();
        $this->registerPermissions();
        $this->registerInfrastructure();
        $this->registerUIComponents();
        $this->registerRoutes();
    }

    private function registerUIComponents(): void
    {
        Livewire::component('app.core.announcement.livewire.admin.announcements', Index::class);
        Livewire::component('app.core.announcement.livewire.admin.announcements.form', Form::class);
        Livewire::component('app.core.announcement.livewire.dashboard.widget', Widget::class);
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
            ->middleware(['api', 'auth:sanctum'])
            ->group(__DIR__.'/../Routes/api.php');
    }
}
