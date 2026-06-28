<?php

namespace App\PlugIns\Cpanel\Providers;

use App\Core\Auth\Permission\Contracts\PermissionRegistryContract;
use App\PlugIns\Cpanel\Commands\EnsureLaravelCronJobs;
use App\PlugIns\Cpanel\Commands\SyncEmailAccounts;
use App\PlugIns\Cpanel\Data\CpanelConfig;
use App\PlugIns\Cpanel\Permissions\CpanelPermissions;
use App\PlugIns\Cpanel\Services\CpanelMailboxManager;
use App\PlugIns\Cpanel\Services\CpanelService;
use App\Core\Identity\Models\User;
use App\Core\Settings\Contracts\SettingsRegistryContract;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class CpanelServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CpanelConfig::class);
        $this->app->singleton(CpanelService::class);
        $this->app->singleton(CpanelMailboxManager::class);
    }

    public function boot(SettingsRegistryContract $settingsRegistry, PermissionRegistryContract $permissionRegistry): void
    {
        $this->registerSettings($settingsRegistry);
        $this->registerPermissions($permissionRegistry);
        $this->registerAuthorization();
        $this->registerInfrastructure();
        $this->registerUIComponents();
        $this->registerRoutes();
        $this->registerCommands();
    }

    private function registerUIComponents(): void
    {
        Livewire::addNamespace('cpanel', classNamespace: 'App\PlugIns\Cpanel\Livewire');
    }

    private function registerInfrastructure(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        $this->loadViewsFrom(__DIR__.'/../Resources/Views', 'cpanel');
    }

    private function registerRoutes(): void
    {
        Route::middleware(['web', 'auth'])
            ->group(__DIR__.'/../Routes/web.php');

        Route::prefix('admin')
            ->name('admin.')
            ->middleware(['web', 'auth', 'can:manage-email-accounts'])
            ->group(__DIR__.'/../Routes/admin.php');
    }

    private function registerCommands(): void
    {
        $this->commands([
            EnsureLaravelCronJobs::class,
            SyncEmailAccounts::class,
        ]);
    }

    private function registerSettings(SettingsRegistryContract $settingsRegistry): void
    {
        $settingsRegistry->registerConfigFile('cpanel', __DIR__.'/../config/settings.php');
    }

    private function registerPermissions(PermissionRegistryContract $permissionRegistry): void
    {
        $permissionRegistry->registerPermissions(CpanelPermissions::all());
    }

    private function registerAuthorization(): void
    {
        Gate::define('manage-email-accounts', function (User $user): bool {
            return $user->isAdmin() || $user->hasPermission('cpanel.manage-email-accounts');
        });
    }
}
