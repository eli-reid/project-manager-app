<?php

namespace App\Core\Settings\Observers;

use App\Core\Settings\Models\SettingsSqlite;
use App\Core\Settings\Services\SettingsCacheService;
use Illuminate\Support\Facades\Log;

/**
 * SettingsObserver
 * 
 * Handles automatic side effects when settings are modified:
 * - Clears relevant caches
 * - Logs changes for audit trail
 * - Dispatches events if needed
 */
class SettingsObserver
{
    /**
     * Cache service instance
     */
    protected SettingsCacheService $cache;

    public function __construct(SettingsCacheService $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Handle the SettingsSqlite "created" event
     */
    public function created(SettingsSqlite $setting): void
    {
        $this->logChange('created', $setting);
        $this->clearCaches($setting);
    }

    /**
     * Handle the SettingsSqlite "updated" event
     */
    public function updated(SettingsSqlite $setting): void
    {
        $this->logChange('updated', $setting);
        $this->clearCaches($setting);
    }

    /**
     * Handle the SettingsSqlite "deleted" event
     */
    public function deleted(SettingsSqlite $setting): void
    {
        $this->logChange('deleted', $setting);
        $this->clearCaches($setting);
    }

    /**
     * Handle the SettingsSqlite "restored" event
     */
    public function restored(SettingsSqlite $setting): void
    {
        $this->logChange('restored', $setting);
        $this->clearCaches($setting);
    }

    /**
     * Clear relevant caches after a setting change
     */
    protected function clearCaches(SettingsSqlite $setting): void
    {
        try {
            // Clear individual setting cache
            $this->cache->forget("setting.{$setting->key}");
            $this->cache->forget("setting.exists.{$setting->key}");

            // Clear group-related caches
            if ($setting->group) {
                $this->cache->forget("settings.group.{$setting->group}");
            }

            // Clear aggregate caches
            $this->cache->forget('settings.all');
            $this->cache->forget('settings.all.grouped');
            $this->cache->forget('settings.public');
        } catch (\Exception $e) {
            Log::warning("Failed to clear settings cache for key '{$setting->key}': " . $e->getMessage());
        }
    }

    /**
     * Log a settings change for audit trail
     */
    protected function logChange(string $action, SettingsSqlite $setting): void
    {
        try {
            // Only log in non-production or with explicit logging enabled
            if (config('app.debug') || env('SETTINGS_LOG_CHANGES', false)) {
                Log::info("Settings change: {$action}", [
                    'key' => $setting->key,
                    'group' => $setting->group,
                    'type' => $setting->type,
                    'is_encrypted' => $setting->encrypted ?? false,
                ]);
            }
        } catch (\Exception $e) {
            // Silently fail - don't break the app for logging issues
        }
    }
}
