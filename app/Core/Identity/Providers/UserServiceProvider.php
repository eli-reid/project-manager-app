<?php

namespace App\Core\Identity\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class UserServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
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
    }

    private function registerRoutes(): void
    {
        Route::middleware(['web'])
            ->group(__DIR__.'/../Routes/settings.php');
    }
}
