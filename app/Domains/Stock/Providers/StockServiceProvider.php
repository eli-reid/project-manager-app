<?php

namespace App\Domains\Stock\Providers;

use App\Core\User\Services\PermissionRegistry;
use App\Domains\Stock\Models\StockOrder;
use App\Domains\Stock\Models\StockOrderTemplate;
use App\Domains\Stock\Permissions\StockOrderPermissions;
use App\Domains\Stock\Permissions\StockOrderTemplatePermissions;
use App\Domains\Stock\Policies\StockOrderPolicy;
use App\Domains\Stock\Policies\StockOrderTemplatePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class StockServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(PermissionRegistry $permissionRegistry): void
    {
        $this->registerPermissions($permissionRegistry);

        Gate::policy(StockOrder::class, StockOrderPolicy::class);
        Gate::policy(StockOrderTemplate::class, StockOrderTemplatePolicy::class);

        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        $this->loadViewsFrom(__DIR__.'/../Resources/Views', 'stock');

        Route::prefix('admin')
            ->name('admin.')
            ->middleware(['web', 'auth'])
            ->group(__DIR__.'/../Routes/admin.php');

        Route::middleware(['web', 'auth', 'verified'])
            ->group(__DIR__.'/../Routes/mobile.php');

        Route::middleware(['web', 'auth', 'verified'])
            ->group(__DIR__.'/../Routes/web.php');

        Route::prefix('api')
            ->name('api.')
            ->middleware(['web', 'auth', 'verified'])
            ->group(__DIR__.'/../Routes/api.php');
    }

    private function registerPermissions(PermissionRegistry $permissionRegistry): void
    {
        $definitions = [
            ...StockOrderPermissions::all(),
            ...StockOrderTemplatePermissions::all(),
        ];

        $permissionRegistry->registerPermissions(array_map(function (array $definition): array {
            $resource = (string) $definition['resource'];
            $action = (string) $definition['action'];

            return [
                'resource' => $resource,
                'action' => $action,
                'label' => $definition['label'] ?? str($resource.' '.$action)->replace(['_', '-'], ' ')->headline()->value(),
                'description' => $definition['description'] ?? '',
            ];
        }, $definitions));
    }
}
