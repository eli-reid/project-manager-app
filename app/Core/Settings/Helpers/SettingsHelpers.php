<?php

use App\Core\Settings\Services\SettingsSqliteService;

if (!function_exists('setting')) {
    /**
     * Get or access the settings service instance
     *
     * Usage:
     *   setting('app.name')                    // Get setting value
     *   setting('app.name', 'default')         // Get with default
     *   setting()->get('app.name')             // Get via service
     *   setting()->all()                       // Get all settings
     *   setting()->set('app.name', 'value')    // Set setting
     *
     * @param  string|null  $key
     * @param  mixed        $default
     * @return mixed|SettingsSqliteService
     */
    function setting(?string $key = null, mixed $default = null): mixed
    {
        $service = app(SettingsSqliteService::class);

        if ($key === null) {
            return $service;
        }

        return $service->get($key, $default);
    }
}

if (!function_exists('settings')) {
    /**
     * Get the settings service instance
     *
     * Usage:
     *   settings()->get('app.name')
     *   settings()->set('app.name', 'value')
     *   settings()->all()
     *   settings()->getGroup('app')
     *
     * @return SettingsSqliteService
     */
    function settings(): SettingsSqliteService
    {
        return app(SettingsSqliteService::class);
    }
}

if (!function_exists('setting_int')) {
    /**
     * Get a setting as an integer
     *
     * @param  string  $key
     * @param  int     $default
     * @return int
     */
    function setting_int(string $key, int $default = 0): int
    {
        return (int) setting($key, $default);
    }
}

if (!function_exists('setting_bool')) {
    /**
     * Get a setting as a boolean
     *
     * @param  string  $key
     * @param  bool    $default
     * @return bool
     */
    function setting_bool(string $key, bool $default = false): bool
    {
        $value = setting($key, $default);

        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            return filter_var($value, FILTER_VALIDATE_BOOLEAN);
        }

        return (bool) $value;
    }
}

if (!function_exists('setting_json')) {
    /**
     * Get a setting as JSON decoded array
     *
     * @param  string  $key
     * @param  array   $default
     * @return array
     */
    function setting_json(string $key, array $default = []): array
    {
        $value = setting($key);

        if ($value === null) {
            return $default;
        }

        if (is_array($value)) {
            return $value;
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : $default;
        }

        return $default;
    }
}

if (!function_exists('setting_has')) {
    /**
     * Check if a setting exists
     *
     * @param  string  $key
     * @return bool
     */
    function setting_has(string $key): bool
    {
        return setting()->has($key);
    }
}

if (!function_exists('setting_set')) {
    /**
     * Set a setting value
     *
     * @param  string  $key
     * @param  mixed   $value
     * @param  string|null  $description
     * @return bool
     */
    function setting_set(string $key, mixed $value, ?string $description = null): bool
    {
        return setting()->set($key, $value, $description);
    }
}
