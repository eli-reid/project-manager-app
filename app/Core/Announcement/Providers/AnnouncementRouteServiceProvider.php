<?php

namespace App\Core\Announcement\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class AnnouncementRouteServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->routes(function () {
            Route::prefix('admin')
                ->middleware(['web', 'auth', 'can:access-admin'])
                ->group(function () {
                    $this->loadRoutesFrom(__DIR__ . '/../Routes/admin.php');
                });
        $this->routes(function () {
            Route::prefix('api')
                ->middleware(['api', 'auth:sanctum'])
                ->group(function () {
                    $this->loadRoutesFrom(__DIR__ . '/../Routes/api.php');
                });
        $this->routes(function () {
            Route::middleware(['web', 'auth'])
                ->group(function () {
                    $this->loadRoutesFrom(__DIR__ . '/../Routes/web.php');
                });
        });
    }
}

