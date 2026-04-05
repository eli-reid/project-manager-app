<?php

namespace App\Domains\Invoices\Providers;

use App\Core\User\Contracts\PermissionRegistryContract;
use App\Domains\Invoices\Livewire\Admin\Invoices\Form;
use App\Domains\Invoices\Livewire\Admin\Invoices\Index;
use App\Domains\Invoices\Livewire\Admin\Invoices\Show;
use App\Domains\Invoices\Models\Invoice;
use App\Domains\Invoices\Permissions\InvoicePermissions;
use App\Domains\Invoices\Policies\InvoicePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class InvoicesServiceProvider extends ServiceProvider
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

    private function registerAuthorization(): void
    {
        Gate::policy(Invoice::class, InvoicePolicy::class);
    }

    private function registerInfrastructure(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        $this->loadViewsFrom(__DIR__.'/../Resources/Views', 'invoices');
    }

    private function registerUIComponents(): void
    {
        Livewire::component('app.domains.invoices.livewire.admin.invoices', Index::class);
        Livewire::component('app.domains.invoices.livewire.admin.invoices.form', Form::class);
        Livewire::component('app.domains.invoices.livewire.admin.invoices.show', Show::class);
    }

    private function registerRoutes(): void
    {
        Route::prefix('admin')
            ->name('admin.')
            ->middleware(['web', 'auth'])
            ->group(__DIR__.'/../Routes/admin.php');
    }

    private function registerPermissions(PermissionRegistryContract $permissionRegistry): void
    {
        $permissionRegistry->registerPermissions(InvoicePermissions::all());
    }
}
