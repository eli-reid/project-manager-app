<?php

namespace App\Core\Settings\Services;

use App\Core\Settings\Models\SettingsSqlite;
use App\Core\Settings\Repositories\SettingsRepository;
use App\Core\Settings\Traits\EncryptableSettings;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SettingsSqliteService
{
    use EncryptableSettings;

    /**
     * Repository instance
     */
    protected SettingsRepository $repository;

    /**
     * Cache service instance
     */
    protected SettingsCacheService $cache;

    /**
     * Cache duration for settings in minutes
     */
    protected int $cacheDuration = 60;

    /**
     * In-memory cache for all settings (loaded once per request)
     */
    protected static ?Collection $allSettings = null;

    /**
     * Flag to track if we've attempted to load all settings
     */
    protected static bool $settingsLoaded = false;

    public function __construct(
        SettingsRepository $repository,
        SettingsCacheService $cache
    ) {
        $this->repository = $repository;
        $this->cache = $cache;
    }

    /**
     * Get a specific setting value with .env fallback and decryption
     */
    public function get(string $key, mixed $default = null): mixed
    {
        // Check if we should use .env file in development mode
        if ($this->shouldUseEnvInDev()) {
            return $this->getFromEnv($key, $default);
        }

        try {
            // Use cache to fetch the setting
            return $this->cache->remember("setting.{$key}", function () use ($key, $default) {
                $setting = $this->repository->find($key);

                return $setting?->value ?? $default;
            });
        } catch (\Exception $e) {
            $this->safeLog('debug', "SQLite settings error for '{$key}': ".$e->getMessage());

            // Final fallback to .env
            return $this->getFromEnv($key, $default);
        }
    }

    /**
     * Pre-load all settings into memory (called once per request by middleware)
     */
    public function preloadAllSettings(): Collection
    {
        // Prevent multiple loads in the same request
        if (self::$settingsLoaded && self::$allSettings !== null) {
            return self::$allSettings;
        }

        try {
            self::$allSettings = $this->cache->remember('settings.all', function () {
                $settings = $this->repository->all();

                return $settings->mapWithKeys(fn ($setting) => [$setting->key => $setting->value]);
            });

            self::$settingsLoaded = true;

            return self::$allSettings;
        } catch (\Exception $e) {
            $this->safeLog('warning', 'Failed to preload all settings: '.$e->getMessage());
            self::$allSettings = collect();
            self::$settingsLoaded = true;

            return self::$allSettings;
        }
    }

    /**
     * Load all settings from database
     */
    protected function loadAllSettingsFromDb(): Collection
    {
        $settings = $this->repository->all();

        return $settings->mapWithKeys(fn ($setting) => [$setting->key => $setting->value]);
    }

    /**
     * Check if cache is available
     */
    protected function isCacheAvailable(): bool
    {
        try {
            return app()->bound('cache') && \Illuminate\Support\Facades\Cache::getStore() !== null;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Safe logging that won't break if Log facade isn't available
     */
    protected function safeLog(string $level, string $message): void
    {
        try {
            if (app()->bound('log')) {
                Log::{$level}($message);
            }
        } catch (\Exception $e) {
            // Silently fail if logging isn't available
        }
    }

    /**
     * Check if a setting exists
     */
    public function has(string $key): bool
    {
        // Check if we should use .env file in development mode
        if ($this->shouldUseEnvInDev()) {
            $envMappings = config('settings-db.env_mappings', []);

            if (isset($envMappings[$key])) {
                return env($envMappings[$key]) !== null;
            }

            // Check direct env key
            return env($this->normalizeEnvKey($key)) !== null;
        }

        try {
            return $this->cache->remember("setting.exists.{$key}", function () use ($key) {
                return $this->repository->exists($key);
            });
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Set a setting value
     */
    public function set(string $key, mixed $value, ?string $description = null): bool
    {
        // Warn if we're in dev mode and trying to set a value that will be read from .env
        if ($this->shouldUseEnvInDev()) {
            $envMappings = config('settings-db.env_mappings', []);
            if (isset($envMappings[$key]) || env($this->normalizeEnvKey($key)) !== null) {
                $this->safeLog('warning', "Attempting to set '{$key}' in dev mode, but .env file will override this value. Set SETTINGS_USE_ENV_IN_DEV=false to use database settings.");
            }
        }

        try {
            $attributes = [];
            if ($description) {
                $attributes['description'] = $description;
            }

            $setting = $this->repository->save($key, $value, $attributes);

            if ($setting) {
                // Clear caches for this key
                $this->cache->forget("setting.{$key}");
                $this->cache->forget("setting.exists.{$key}");
                $this->cache->flush();
                $this->resetInMemoryCache();

                return true;
            }

            return false;
        } catch (\Exception $e) {
            $this->safeLog('error', "Failed to set setting '{$key}': ".$e->getMessage());

            return false;
        }
    }

    /**
     * Get all settings grouped by their group
     */
    public function getAllGrouped(): Collection
    {
        try {
            return $this->cache->remember('settings.all.grouped', function () {
                return $this->repository->all()
                    ->sortBy('group')
                    ->groupBy('group');
            });
        } catch (\Exception $e) {
            $this->safeLog('warning', 'Failed to get grouped settings: '.$e->getMessage());

            return collect();
        }
    }

    /**
     * Get settings for a specific group
     */
    public function getGroup(string $group): Collection
    {
        try {
            return $this->cache->remember("settings.group.{$group}", function () use ($group) {
                return $this->repository->all($group)
                    ->sortBy('order');
            });
        } catch (\Exception $e) {
            $this->safeLog('warning', "Failed to get settings for group '{$group}': ".$e->getMessage());

            return collect();
        }
    }

    /**
     * Bulk update settings
     */
    public function updateMany(array $settings): array
    {
        $results = [];

        foreach ($settings as $key => $value) {
            try {
                $success = $this->set($key, $value);
                $results[$key] = ['success' => $success];
            } catch (\Exception $e) {
                $results[$key] = ['success' => false, 'error' => $e->getMessage()];
            }
        }

        // Flush all caches after bulk update
        $this->cache->flush();
        $this->resetInMemoryCache();

        return $results;
    }

    /**
     * Get settings that are safe for frontend use (public settings)
     */
    public function getPublicSettings(): Collection
    {
        try {
            return $this->cache->remember('settings.public', function () {
                return $this->repository->all()
                    ->where('is_public', true)
                    ->map(fn ($setting) => [
                        'key' => $setting->key,
                        'value' => $setting->value,
                        'display_name' => $setting->display_name,
                        'group' => $setting->group,
                    ]);
            });
        } catch (\Exception $e) {
            $this->safeLog('warning', 'Failed to get public settings: '.$e->getMessage());

            return collect();
        }
    }

    /**
     * Get multiple settings at once with decryption
     */
    public function getMultiple(array $keys): array
    {
        $results = [];
        foreach ($keys as $key) {
            $results[$key] = $this->get($key);
        }

        return $results;
    }

    /**
     * Get settings formatted for form display
     */
    public function getFormData(?string $group = null): Collection
    {
        try {
            $settings = $this->repository->all($group)
                ->where('is_visible', true)
                ->sortBy([
                    ['group', 'asc'],
                    ['order', 'asc'],
                ]);

            return $settings->map(function ($setting) {
                return [
                    'key' => $setting->key,
                    'value' => $setting->value,
                    'raw_value' => $setting->getRawOriginal('value'),
                    'display_name' => $setting->display_name,
                    'description' => $setting->description,
                    'type' => $setting->type,
                    'group' => $setting->group,
                    'options' => $setting->options,
                    'is_required' => $setting->is_required ?? false,
                    'is_encrypted' => $setting->encrypted ?? false,
                ];
            });
        } catch (\Exception $e) {
            $this->safeLog('warning', 'Failed to get form data: '.$e->getMessage());

            return collect();
        }
    }

    /**
     * Clear all settings cache
     */
    public function clearAllCache(): void
    {
        $this->cache->flush();
        $this->resetInMemoryCache();
    }

    /**
     * Initialize the settings database
     */
    public function initializeDatabase(): bool
    {
        try {
            $model = new SettingsSqlite;
            $model->ensureSettingsDatabase();

            return true;
        } catch (\Exception $e) {
            $this->safeLog('error', 'Failed to initialize settings database: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Get the path to the settings database
     */
    public function getDatabasePath(): string
    {
        return config('settings-db.database_path', base_path('settings.data'));
    }

    /**
     * Check if settings database exists and is accessible
     */
    public function isDatabaseAvailable(): bool
    {
        try {
            $dbPath = $this->getDatabasePath();

            if (! file_exists($dbPath)) {
                // Try to create it
                $this->initializeDatabase();
            }

            // Test connection
            SettingsSqlite::count();

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get settings organized for setup wizard
     */
    public function getSetupWizardSteps(): array
    {
        try {
            $allSettings = $this->repository->all()
                ->where('is_visible', true)
                ->sortBy([
                    ['group', 'asc'],
                    ['order', 'asc'],
                ])
                ->groupBy('group');

            $steps = [];
            $stepOrder = [
                'app' => 'Application Settings',
                'database' => 'Database Configuration',
                'mail' => 'Email Settings',
                'security' => 'Security Settings',
                'features' => 'Feature Settings',
                'advanced' => 'Advanced Settings',
            ];

            foreach ($stepOrder as $group => $title) {
                if ($allSettings->has($group)) {
                    $settings = $allSettings[$group]->map(function ($setting) {
                        return [
                            'key' => $setting->key,
                            'value' => $setting->value,
                            'display_name' => $setting->display_name,
                            'description' => $setting->description,
                            'type' => $setting->type,
                            'options' => $setting->options,
                            'is_required' => $setting->is_required ?? false,
                            'is_encrypted' => $setting->encrypted ?? false,
                        ];
                    });

                    $steps[] = [
                        'id' => $group,
                        'title' => $title,
                        'settings' => $settings,
                        'required_count' => $settings->where('is_required', true)->count(),
                        'total_count' => $settings->count(),
                    ];
                }
            }

            return $steps;
        } catch (\Exception $e) {
            $this->safeLog('error', 'Failed to get setup wizard steps: '.$e->getMessage());

            return [];
        }
    }

    /**
     * Check if we should use .env file in development mode
     */
    protected function shouldUseEnvInDev(): bool
    {
        $devModeConfig = config('settings-db.dev_mode', []);

        // Check if dev mode is enabled
        if (! ($devModeConfig['use_env_file'] ?? false)) {
            return false;
        }

        // Check if current environment is in the allowed list
        $currentEnv = app()->environment();
        $allowedEnvs = $devModeConfig['enabled_environments'] ?? ['local', 'development', 'dev', 'testing'];

        return in_array($currentEnv, $allowedEnvs);
    }

    /**
     * Get setting value from .env file using mappings
     */
    protected function getFromEnv(string $key, mixed $default = null): mixed
    {
        $envMappings = config('settings-db.env_mappings', []);

        if (isset($envMappings[$key])) {
            $envKey = $envMappings[$key];
            $value = env($envKey, $default);

            // Log that we're using .env in dev mode for debugging
            $this->safeLog('debug', "Using .env value for setting '{$key}' from env key '{$envKey}' in dev mode");

            return $value;
        }

        // If no mapping exists, try to use the key directly as env key
        $directValue = env($this->normalizeEnvKey($key), null);
        if ($directValue !== null) {
            $this->safeLog('debug', "Using direct .env value for setting '{$key}' in dev mode");

            return $directValue;
        }

        return $default;
    }

    /**
     * Validate a setting value
     */
    public function validate(string $key, mixed $value): array
    {
        try {
            $setting = SettingsSqlite::where('key', $key)->first();

            if (! $setting) {
                return [
                    'valid' => false,
                    'errors' => ['Setting not found'],
                ];
            }

            $errors = [];

            // Required validation
            if ($setting->is_required && (is_null($value) || $value === '')) {
                $errors[] = "The {$setting->display_name} field is required.";
            }

            // Skip further validation if value is empty and not required
            if ((is_null($value) || $value === '') && ! $setting->is_required) {
                return ['valid' => true, 'errors' => []];
            }

            // Type-specific validation
            switch ($setting->type) {
                case 'email':
                    if (! filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        $errors[] = "The {$setting->display_name} must be a valid email address.";
                    }
                    break;

                case 'url':
                    if (! filter_var($value, FILTER_VALIDATE_URL)) {
                        $errors[] = "The {$setting->display_name} must be a valid URL.";
                    }
                    break;

                case 'number':
                    if (! is_numeric($value)) {
                        $errors[] = "The {$setting->display_name} must be a number.";
                    }
                    break;

                case 'integer':
                    if (! filter_var($value, FILTER_VALIDATE_INT)) {
                        $errors[] = "The {$setting->display_name} must be an integer.";
                    }
                    break;

                case 'boolean':
                    if (! in_array($value, [true, false, 1, 0, '1', '0', 'true', 'false'], true)) {
                        $errors[] = "The {$setting->display_name} must be true or false.";
                    }
                    break;

                case 'select':
                    if ($setting->options) {
                        $options = is_string($setting->options) ? json_decode($setting->options, true) : $setting->options;
                        if (is_array($options) && ! array_key_exists($value, $options)) {
                            $errors[] = "The {$setting->display_name} must be one of the available options.";
                        }
                    }
                    break;

                case 'password':
                    if (strlen($value) < 8) {
                        $errors[] = "The {$setting->display_name} must be at least 8 characters long.";
                    }
                    break;
            }

            return [
                'valid' => empty($errors),
                'errors' => $errors,
            ];

        } catch (\Exception $e) {
            return [
                'valid' => false,
                'errors' => ['Validation error: '.$e->getMessage()],
            ];
        }
    }

    /**
     * Normalize a setting key into a conventional environment variable key.
     */
    protected function normalizeEnvKey(string $key): string
    {
        return Str::upper((string) preg_replace('/[^A-Za-z0-9]+/', '_', $key));
    }

    /**
     * Clear request-scoped in-memory settings cache.
     */
    protected function resetInMemoryCache(): void
    {
        self::$allSettings = null;
        self::$settingsLoaded = false;
    }
}
