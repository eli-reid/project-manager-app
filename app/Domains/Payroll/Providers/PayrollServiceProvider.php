<?php

namespace App\Domains\Payroll\Providers;

use App\Core\Auth\Permission\Contracts\PermissionRegistryContract;
use App\Domains\Payroll\Permissions\PayrollPermissions;
use App\Domains\Payroll\Policies\PayrollPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class PayrollServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(PermissionRegistryContract $permissionRegistry): void
    {
        $this->registerPermissions($permissionRegistry);
        $this->registerAuthorization();
        $this->registerInfrastructure();
    }

    /**
     * Register authorization policies for payroll domain.
     */
    private function registerAuthorization(): void
    {
        Gate::policy('payroll', PayrollPolicy::class);
    }

    /**
     * Register infrastructure for payroll domain.
     */
    private function registerInfrastructure(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
    }

    /**
     * Register permissions for payroll domain.
     */
    private function registerPermissions(PermissionRegistryContract $permissionRegistry): void
    {
        $permissionRegistry->registerPermissions(PayrollPermissions::all());
    }
}
