<?php

namespace App\Core\User\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class UserServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->routes(function () {
            Route::prefix('admin')
                ->middleware(['web', 'auth', 'can:access-admin'])
                ->group(function () {
                    $this->loadRoutesFrom(__DIR__ . '/../Routes/admin/users.php');
                    $this->loadRoutesFrom(__DIR__ . '/../Routes/admin/roles.php');
                });
        });
    }
}

