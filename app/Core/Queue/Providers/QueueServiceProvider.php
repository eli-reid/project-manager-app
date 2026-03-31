<?php

namespace App\Core\Queue\Providers;

use App\Core\Queue\Livewire\Admin\QueueMonitor;
use App\Core\Queue\Permissions\QueuePermissions;
use App\Core\Queue\Services\QueueService;
use App\Core\User\Models\User;
use App\Core\User\Services\PermissionRegistry;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class QueueServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(QueueService::class, function (): QueueService {
            return new QueueService;
        });
    }

    public function boot(): void
    {
        $this->registerPermissions();
        $this->registerAuthorizationGates();
        $this->loadViewsFrom(__DIR__.'/../Resources/Views', 'queue');
        Livewire::component('app.core.queue.livewire.admin.queue-monitor', QueueMonitor::class);
        $this->configureRoutes();
    }

    private function registerPermissions(): void
    {
        /** @var PermissionRegistry $registry */
        $registry = $this->app->make(PermissionRegistry::class);

        $registry->registerPermissions(array_map(function (array $definition): array {
            $resource = (string) $definition['resource'];
            $action = (string) $definition['action'];

            return [
                'resource' => $resource,
                'action' => $action,
                'label' => $definition['label'] ?? str($resource.' '.$action)->replace(['_', '-'], ' ')->headline()->value(),
                'description' => $definition['description'] ?? '',
                'built_in_roles' => $definition['built_in_roles'] ?? [],
            ];
        }, QueuePermissions::all()));
    }

    private function registerAuthorizationGates(): void
    {
        Gate::define('queue.manage', fn (User $user): bool => $user->hasPermission('queue.manage'));
    }

    private function configureRoutes(): void
    {
        if ($this->app->routesAreCached()) {
            return;
        }

        $this->loadRoutesFrom(__DIR__.'/../Routes/admin.php');
    }
}
