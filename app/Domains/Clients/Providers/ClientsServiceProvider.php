<?php

namespace App\Domains\Clients\Providers;

use App\Core\User\Services\PermissionRegistry;
use App\Domains\Clients\Livewire\Admin\Clients\Form;
use App\Domains\Clients\Livewire\Admin\Clients\Index;
use App\Domains\Clients\Livewire\Admin\Clients\InlineCreateWidget;
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
        $this->registerAuthorization();
        $this->registerInfrastructure();
        $this->registerUIComponents();
        $this->registerRoutes();
    }

    private function registerAuthorization(): void
    {
        Gate::policy(Client::class, ClientPolicy::class);
    }

    private function registerInfrastructure(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        $this->loadViewsFrom(__DIR__.'/../Resources/Views', 'clients');
    }

    private function registerUIComponents(): void
    {
        Livewire::component('app.domains.clients.livewire.admin.clients', Index::class);
        Livewire::component('app.domains.clients.livewire.admin.clients.form', Form::class);
        Livewire::component('app.domains.clients.livewire.admin.clients.inline-create-widget', InlineCreateWidget::class);
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
        }, ClientPermissions::all()));
    }
}
