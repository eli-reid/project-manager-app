<?php

namespace App\Domains\Invoices\Providers;

use App\Core\User\Services\PermissionRegistry;
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

    public function boot(PermissionRegistry $permissionRegistry): void
    {
        $this->registerPermissions($permissionRegistry);
        Gate::policy(Invoice::class, InvoicePolicy::class);
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        $this->loadViewsFrom(__DIR__.'/../Resources/Views', 'invoices');

        Livewire::component('app.domains.invoices.livewire.admin.invoices', Index::class);
        Livewire::component('app.domains.invoices.livewire.admin.invoices.form', Form::class);
        Livewire::component('app.domains.invoices.livewire.admin.invoices.show', Show::class);

        Route::prefix('admin')
            ->name('admin.')
            ->middleware(['web', 'auth'])
            ->group(__DIR__.'/../Routes/admin.php');
    }

    private function registerPermissions(PermissionRegistry $permissionRegistry): void
    {
        $permissionRegistry->registerPermissions(array_map(function (array $definition): array {
            $resource = (string) $definition['resource'];
            $action = (string) $definition['action'];

            return [
                'resource' => $resource,
                'action' => $action,
                'label' => $definition['label'] ?? str($resource.' '.$action)->replace(['_', '-'], ' ')->headline()->value(),
                'description' => $definition['description'] ?? '',
            ];
        }, InvoicePermissions::all()));
    }
}
