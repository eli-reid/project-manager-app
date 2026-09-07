<?php

declare(strict_types=1);

namespace App\Domains\Plans\Providers;

use App\Core\Assets\Services\AssetReferencerRegistry;
use App\Core\Auth\Permission\Contracts\PermissionRegistryContract;
use App\Core\Settings\Contracts\SettingsRegistryContract;
use App\Domains\Plans\Console\Commands\CheckRasterizerCommand;
use App\Domains\Plans\Console\Commands\PrunePlanDerivativesCommand;
use App\Domains\Plans\Contracts\PlanRasterizerContract;
use App\Domains\Plans\Models\PlanAnnotation;
use App\Domains\Plans\Models\PlanLink;
use App\Domains\Plans\Models\PlanSet;
use App\Domains\Plans\Models\PlanSheet;
use App\Domains\Plans\Permissions\PlanPermissions;
use App\Domains\Plans\Policies\PlanAnnotationPolicy;
use App\Domains\Plans\Policies\PlanLinkPolicy;
use App\Domains\Plans\Policies\PlanSetPolicy;
use App\Domains\Plans\Policies\PlanSheetPolicy;
use App\Domains\Plans\Services\PlanAssetAccessResolver;
use App\Domains\Plans\Services\PlanRasterizerManager;
use App\Domains\Plans\Services\Rasterizers\NullRasterizer;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

final class PlansServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PlanRasterizerManager::class);
        $this->app->bind(PlanRasterizerContract::class, function ($app): PlanRasterizerContract {
            if ($app->environment('testing')) {
                return new NullRasterizer;
            }

            return $app->make(PlanRasterizerManager::class)->driver();
        });
        $this->commands([CheckRasterizerCommand::class, PrunePlanDerivativesCommand::class]);
    }

    public function boot(
        PermissionRegistryContract $permissionRegistry,
        SettingsRegistryContract $settingsRegistry,
        AssetReferencerRegistry $assetRegistry,
    ): void {
        $permissionRegistry->registerPermissions(PlanPermissions::all());
        $settingsRegistry->registerConfigFile('plans', __DIR__.'/../config/settings.php');
        $this->loadViewsFrom(__DIR__.'/../Resources/Views', 'plans');
        Livewire::addNamespace('plans', classNamespace: 'App\\Domains\\Plans\\Livewire');
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        $assetRegistry->register('plans', PlanAssetAccessResolver::class);
        Gate::policy(PlanSet::class, PlanSetPolicy::class);
        Gate::policy(PlanSheet::class, PlanSheetPolicy::class);
        Gate::policy(PlanAnnotation::class, PlanAnnotationPolicy::class);
        Gate::policy(PlanLink::class, PlanLinkPolicy::class);
        if ((bool) config('plans.enabled', false)) {
            Route::middleware(['web', 'auth', 'verified'])
                ->group(__DIR__.'/../Routes/web.php');
        }
    }
}
