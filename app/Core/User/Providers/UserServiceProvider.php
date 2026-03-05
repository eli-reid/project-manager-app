<?php

namespace App\Core\User\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class UserServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->configureRoutes();
    }


    private function configureRoutes(): void
    {
        Route::prefix('admin')
            ->middleware(['web', 'auth', 'can:access-admin'])
            ->group(__DIR__ . '/../Routes/users/admin.php');

        /**Route::middleware(['web', 'auth'])
            ->group(__DIR__ . '/../Routes/web.php');

        Route::middleware(['mobile', 'auth'])
            ->group(__DIR__ . '/../Routes/mobile.php');

        Route::prefix('api')
            ->middleware(['api', 'auth:sanctum'])
            ->group(__DIR__ . '/../Routes/api.php');**/
    }
}