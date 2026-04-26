<?php

namespace App\Domains\Submittals\Providers;

use App\Domains\Submittals\Models\Submittal;
use App\Domains\Submittals\Policies\SubmittalPolicy;
use App\Domains\Submittals\Permissions\SubmittalPermissions;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class SubmittalsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->registerAuthorization();
        $this->registerInfrastructure();
        $this->registerUIComponents();
        $this->registerRoutes();
        $this->registerPermissions();
    }

    private function registerAuthorization(): void
    {
        Gate::policy(Submittal::class, SubmittalPolicy::class);
    }

    private function registerInfrastructure(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        $this->loadViewsFrom(__DIR__.'/../Resources/Views', 'submittals');
    }

    private function registerUIComponents(): void
    {
        Livewire::addNamespace('submittals', classNamespace: 'App\\Domains\\Submittals\\Livewire');
    }

    private function registerRoutes(): void
    {
        Route::prefix('admin')
            ->name('admin.')
            ->middleware(['web', 'auth'])
            ->group(__DIR__.'/../Routes/admin.php');

        Route::middleware(['web', 'auth', 'verified'])
            ->group(__DIR__.'/../Routes/web.php');

        Route::middleware(['web', 'auth', 'verified'])
            ->group(__DIR__.'/../Routes/mobile.php');
    }

    private function registerPermissions(): void
    {
        if (method_exists(app(), 'make')) {
            $permissionRegistry = app('permission.registry');
            if ($permissionRegistry) {
                $permissionRegistry->registerPermissions(SubmittalPermissions::all());
            }
        }
    }
}
