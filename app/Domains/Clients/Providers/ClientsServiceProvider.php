<?php

namespace App\Domains\Clients\Providers;

use App\Core\User\Services\PermissionRegistry;
use App\Domains\Clients\Models\Client;
use App\Domains\Clients\Permissions\ClientPermissions;
use App\Domains\Clients\Policies\ClientPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class ClientsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(PermissionRegistry $permissionRegistry): void
    {
        $this->registerPermissions($permissionRegistry);

        Gate::policy(Client::class, ClientPolicy::class);
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        $this->loadViewsFrom(__DIR__.'/../Resources/Views', 'clients');

        Livewire::component('app.domains.clients.livewire.admin.clients', \App\Domains\Clients\Livewire\Admin\Clients\Index::class);
        Livewire::component('app.domains.clients.livewire.admin.clients.form', \App\Domains\Clients\Livewire\Admin\Clients\Form::class);
        Livewire::component('app.domains.clients.livewire.admin.clients.inline-create-widget', \App\Domains\Clients\Livewire\Admin\Clients\InlineCreateWidget::class);

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
        }, ClientPermissions::all()));
    }
}
