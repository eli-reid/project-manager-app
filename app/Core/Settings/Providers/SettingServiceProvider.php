<?php

namespace App\Core\Settings\Providers;

use App\Core\Auth\Permission\Contracts\PermissionRegistryContract;
use App\Core\Settings\Commands\RotateAppKeyCommand;
use App\Core\Settings\Contracts\SettingsRegistryContract;
use App\Core\Settings\Models\SettingsSqlite;
use App\Core\Settings\Observers\SettingsObserver;
use App\Core\Settings\Permissions\SettingsPermissions;
use App\Core\Settings\Policies\SettingPolicy;
use App\Core\Settings\Repositories\SettingsRepository;
use App\Core\Settings\Services\DomainSettingsSynchronizer;
use App\Core\Settings\Services\SettingsCacheService;
use App\Core\Settings\Services\SettingsDatabaseProvisioner;
use App\Core\Settings\Services\SettingsRegistry;
use App\Core\Settings\Services\SettingsSqliteService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class SettingServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register cache service as singleton
        $this->app->singleton(SettingsCacheService::class, function () {
            return new SettingsCacheService;
        });

        // Register repository as singleton
        $this->app->singleton(SettingsRepository::class, function () {
            return new SettingsRepository;
        });

        // Register provisioner as singleton so the $databaseEnsured flag is shared
        $this->app->singleton(SettingsDatabaseProvisioner::class);

        // Register settings service as singleton with dependencies injected
        $this->app->singleton(SettingsSqliteService::class, function ($app) {
            return new SettingsSqliteService(
                $app->make(SettingsRepository::class),
                $app->make(SettingsCacheService::class),
                $app->make(SettingsDatabaseProvisioner::class)
            );
        });

        // Register domain settings synchronizer
        $this->app->singleton(SettingsRegistry::class);
        $this->app->singleton(SettingsRegistryContract::class, SettingsRegistry::class);

        $this->app->singleton(DomainSettingsSynchronizer::class, function ($app): DomainSettingsSynchronizer {
            return new DomainSettingsSynchronizer($app->make(SettingsRegistryContract::class));
        });

        $this->app->alias(SettingsSqliteService::class, 'Settings');
    }

    /**
     * Bootstrap services.
     */
    public function boot(PermissionRegistryContract $permissionRegistry, SettingsRegistryContract $settingsRegistry, SettingsDatabaseProvisioner $settingsDatabaseProvisioner, DomainSettingsSynchronizer $domainSettingsSynchronizer): void
    {
        $this->registerAuthorization();
        $this->registerCommands();
        $this->registerInfrastructure();
        $this->registerObservers();
        $this->registerSettings($settingsRegistry);
        $this->registerPermissions($permissionRegistry);

        // Initialize settings database early (no database config needed)
        $this->initializeSettingsDatabase($settingsDatabaseProvisioner);

        // Sync settings after all providers have had a chance to register their definitions.
        $this->app->booted(function () use ($domainSettingsSynchronizer): void {
            $this->syncDomainSettings($domainSettingsSynchronizer);
        if ($this->app->runningInConsole()) {
            return;
        }

        $this->app->make(SettingsSqliteService::class)->preloadAllSettings();
        });
    }

    private function registerPermissions(PermissionRegistryContract $permissionRegistry): void
    {
        $this->app->booted(function () use ($permissionRegistry): void {
            $permissionRegistry->registerPermissions(SettingsPermissions::all());
        });
    }

    private function registerAuthorization(): void
    {
        Gate::policy(SettingsSqlite::class, SettingPolicy::class);

        Gate::define('settings.view', function (Authenticatable $user): bool {
            return Gate::forUser($user)->allows('viewAny', SettingsSqlite::class);
        });

        Gate::define('settings.edit', function (Authenticatable $user): bool {
            return Gate::forUser($user)->allows('update', SettingsSqlite::class);
        });
    }

    private function registerInfrastructure(): void
    {
        $this->loadViewsFrom(__DIR__.'/../Resources/Views', 'core');
        $this->loadRoutesFrom(__DIR__.'/../Routes/admin.php');
        Livewire::addNamespace('core.settings', classNamespace: 'App\Core\Settings\Livewire');
    }

    private function registerCommands(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            RotateAppKeyCommand::class,
        ]);
    }

    private function registerObservers(): void
    {
        SettingsSqlite::observe(SettingsObserver::class);
    }

    private function registerSettings(SettingsRegistryContract $settingsRegistry): void
    {
        $settingsRegistry->registerConfigFile('app', config_path('settings.php'));
    }

    /**
     * Initialize the settings database
     */
    private function initializeSettingsDatabase(SettingsDatabaseProvisioner $settingsDatabaseProvisioner): void
    {
        try {
            $devModeConfig = config('settings-db.dev_mode', []);
            $useEnvInDev = $devModeConfig['use_env_file'] ?? false;
            $currentEnv = $this->app->environment();
            $allowedEnvs = $devModeConfig['enabled_environments'] ?? ['local', 'development', 'dev', 'testing'];
            $isInDevMode = $useEnvInDev && in_array($currentEnv, $allowedEnvs);

            if ($isInDevMode) {
                if ($this->app->bound('log')) {
                    Log::info('Settings service running in dev mode - using .env file instead of database');
                }

                return;
            }

            $settingsDatabaseProvisioner->ensureDatabase();
        } catch (\Exception $e) {
            if ($this->app->bound('log')) {
                Log::warning('Failed to initialize settings database: '.$e->getMessage());
            }
        }
    }

    /**
     * Synchronize settings defined by domain-level config providers.
     */
    private function syncDomainSettings(DomainSettingsSynchronizer $domainSettingsSynchronizer): void
    {
        $syncOnBoot = config('settings-db.sync.on_boot');
        if ($syncOnBoot === null) {
            $syncOnBoot = ! $this->app->environment('production');
        }

        if (! (bool) $syncOnBoot) {
            return;
        }

        try {
            $changes = $domainSettingsSynchronizer->syncIfChanged();

            if ($changes > 0 && $this->app->bound('log')) {
                Log::info('Domain settings synchronized', ['changes' => $changes]);
            }
        } catch (\Exception $e) {
            if ($this->app->bound('log')) {
                Log::warning('Failed to synchronize domain settings: '.$e->getMessage());
            }
        }
    }
}
