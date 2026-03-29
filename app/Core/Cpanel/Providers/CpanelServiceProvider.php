<?php

namespace App\Core\Cpanel\Providers;

use App\Core\Cpanel\Commands\SyncEmailAccounts;
use App\Core\Cpanel\Data\CpanelConfig;
use App\Core\Cpanel\Permissions\CpanelPermissions;
use App\Core\Cpanel\Services\CpanelMailboxManager;
use App\Core\Cpanel\Services\CpanelService;
use App\Core\User\Models\User;
use App\Core\User\Services\PermissionRegistry;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class CpanelServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CpanelConfig::class, function (): CpanelConfig {
            return CpanelConfig::fromServicesConfig(config('services.cpanel'));
        });

        $this->app->singleton(CpanelService::class, function ($app): CpanelService {
            return new CpanelService($app->make(CpanelConfig::class));
        });

        $this->app->singleton(CpanelMailboxManager::class, function ($app): CpanelMailboxManager {
            return new CpanelMailboxManager($app->make(CpanelService::class));
        });
    }

    public function boot(): void
    {
        $this->registerPermissions();
        $this->registerAuthorizationGates();
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        Route::middleware(['web', 'auth'])
            ->group(__DIR__.'/../Routes/web.php');

        Route::prefix('admin')
            ->name('admin.')
            ->middleware(['web', 'auth', 'can:manage-email-accounts'])
            ->group(__DIR__.'/../Routes/admin.php');

        $this->commands([
            SyncEmailAccounts::class,
        ]);
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
            ];
        }, CpanelPermissions::all()));
    }

    private function registerAuthorizationGates(): void
    {
        Gate::define('manage-email-accounts', function (User $user): bool {
            return $user->isAdmin() || $user->hasPermission('cpanel.manage-email-accounts');
        });
    }
}
