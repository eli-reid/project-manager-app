<?php

namespace App\Domains\Accounting\Providers;

use App\Core\Auth\Permission\Contracts\PermissionRegistryContract;
use App\Domains\Accounting\Models\AccountingCode;
use App\Domains\Accounting\Permissions\AccountingCodePermissions;
use App\Domains\Accounting\Policies\AccountingCodePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AccountingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(PermissionRegistryContract $permissionRegistry): void
    {
        $this->registerPermissions($permissionRegistry);
        $this->registerAuthorization();
        $this->registerInfrastructure();
        $this->registerUIComponents();
        $this->registerRoutes();
    }

    private function registerPermissions(PermissionRegistryContract $permissionRegistry): void
    {
        $permissionRegistry->registerPermissions(AccountingCodePermissions::all());
    }

    private function registerAuthorization(): void
    {
        Gate::policy(AccountingCode::class, AccountingCodePolicy::class);
    }

    private function registerInfrastructure(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        $this->loadViewsFrom(__DIR__.'/../Resources/Views', 'accounting');
    }

    private function registerUIComponents(): void
    {
        Livewire::addNamespace('accounting', classNamespace: 'App\\Domains\\Accounting\\Livewire');
    }

    private function registerRoutes(): void
    {
        Route::prefix('admin')
            ->name('admin.')
            ->middleware(['web', 'auth'])
            ->group(__DIR__.'/../Routes/admin.php');
    }

    Private function registerSettings(): void
    {
        // Register settings here if needed in the future
    }
}
