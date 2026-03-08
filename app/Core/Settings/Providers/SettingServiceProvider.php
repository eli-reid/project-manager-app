<?php

namespace App\Core\Settings\Providers;

use App\Core\Settings\Models\SettingsSqlite;
use App\Core\Settings\Observers\SettingsObserver;
use App\Core\Settings\Repositories\SettingsRepository;
use App\Core\Settings\Services\DomainSettingsSynchronizer;
use App\Core\Settings\Services\SettingsCacheService;
use App\Core\Settings\Services\SettingsSqliteService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

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

        // Register settings service as singleton with dependencies injected
        $this->app->singleton(SettingsSqliteService::class, function ($app) {
            return new SettingsSqliteService(
                $app->make(SettingsRepository::class),
                $app->make(SettingsCacheService::class)
            );
        });

        // Register domain settings synchronizer
        $this->app->singleton(DomainSettingsSynchronizer::class, function () {
            return new DomainSettingsSynchronizer;
        });

        // Create aliases for easier access
        $this->app->alias(SettingsSqliteService::class, 'settings');
        $this->app->alias(SettingsSqliteService::class, 'setting');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Load views from the Settings module
        $this->loadViewsFrom(__DIR__.'/../Resources/Views', 'core');

        // Load routes from the Settings module
        $this->loadRoutesFrom(__DIR__.'/../Routes/admin.php');

        // Register the settings observer for auto cache clearing
        SettingsSqlite::observe(SettingsObserver::class);

        // Initialize settings database early (no database config needed)
        $this->initializeSettingsDatabase();

        // Sync domain-defined settings defaults when domain config files change.
        $this->syncDomainSettings();
    }

    /**
     * Initialize the settings database
     */
    private function initializeSettingsDatabase(): void
    {
        try {
            // Get the settings service
            $settingsService = $this->app->make(SettingsSqliteService::class);

            // Check if we're using .env in dev mode
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

            // Initialize the database if it doesn't exist
            if (! $settingsService->isDatabaseAvailable()) {
                $settingsService->initializeDatabase();
            }
        } catch (\Exception $e) {
            // Log warning but don't break the app if settings initialization fails
            if ($this->app->bound('log')) {
                Log::warning('Failed to initialize settings database: '.$e->getMessage());
            }
        }
    }

    /**
     * Synchronize settings defined by domain-level config providers.
     */
    private function syncDomainSettings(): void
    {
        $syncOnBoot = config('settings-db.sync.on_boot');
        if ($syncOnBoot === null) {
            $syncOnBoot = ! $this->app->environment('production');
        }

        if (! (bool) $syncOnBoot) {
            return;
        }

        try {
            /** @var DomainSettingsSynchronizer $synchronizer */
            $synchronizer = $this->app->make(DomainSettingsSynchronizer::class);
            $changes = $synchronizer->syncIfChanged();

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
