<?php

namespace App\Domains\Addresses\Providers;

use App\Core\User\Services\PermissionRegistry;
use App\Domains\Addresses\Livewire\Admin\Addresses\Form;
use App\Domains\Addresses\Livewire\Admin\Addresses\Index;
use App\Domains\Addresses\Livewire\Admin\Addresses\InlineCreateWidget;
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
        $this->registerAuthorization();
        $this->registerInfrastructure();
        $this->registerUIComponents();
        $this->registerRoutes();
    }

    private function registerAuthorization(): void
    {
        Gate::policy(Address::class, AddressPolicy::class);
    }

    private function registerInfrastructure(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        $this->loadViewsFrom(__DIR__.'/../Resources/Views', 'addresses');
    }

    private function registerUIComponents(): void
    {
        Livewire::component('app.domains.addresses.livewire.admin.addresses', Index::class);
        Livewire::component('app.domains.addresses.livewire.admin.addresses.form', Form::class);
        Livewire::component('app.domains.addresses.livewire.admin.addresses.inline-create-widget', InlineCreateWidget::class);
    }

    private function registerRoutes(): void
    {
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
