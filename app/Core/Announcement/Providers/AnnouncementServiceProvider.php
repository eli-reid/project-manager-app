<?php

namespace App\Core\Announcement\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class AnnouncementServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureActions();
        $this->configureViews();
        $this->configureRoutes();
    }

    /**
     * Configure Announcement actions.
     */
    private function configureActions(): void
    {
        // Configure any actions related to announcements here
    }

    /**
     * Configure Announcement views.
     */
    private function configureViews(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../Resources/Views', 'announcement');

    }

    private function configureRoutes(): void 
    {
         Route::prefix('admin')
            ->middleware(['web', 'auth', 'can:access-admin'])
            ->group(__DIR__ . '/../Routes/admin.php');

        Route::middleware(['web', 'auth'])
            ->group(__DIR__ . '/../Routes/web.php');

        Route::middleware(['mobile', 'auth'])
            ->group(__DIR__ . '/../Routes/mobile.php');

        Route::prefix('api')
            ->middleware(['api', 'auth:sanctum'])
            ->group(__DIR__ . '/../Routes/api.php');
    }
}
