<?php

namespace App\Domains\Addresses\Providers;

use App\Core\User\Services\PermissionRegistry;
use App\Domains\Addresses\Models\Address;
use App\Domains\Addresses\Permissions\AddressPermissions;
use App\Domains\Addresses\Policies\AddressPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AddressesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(PermissionRegistry $permissionRegistry): void
    {
        $this->registerPermissions($permissionRegistry);

        Gate::policy(Address::class, AddressPolicy::class);
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        $this->loadViewsFrom(__DIR__.'/../Resources/Views', 'addresses');

        Livewire::component('app.domains.addresses.livewire.admin.addresses', \App\Domains\Addresses\Livewire\Admin\Addresses\Index::class);
        Livewire::component('app.domains.addresses.livewire.admin.addresses.form', \App\Domains\Addresses\Livewire\Admin\Addresses\Form::class);
        Livewire::component('app.domains.addresses.livewire.admin.addresses.inline-create-widget', \App\Domains\Addresses\Livewire\Admin\Addresses\InlineCreateWidget::class);

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
        }, AddressPermissions::all()));
    }
}
