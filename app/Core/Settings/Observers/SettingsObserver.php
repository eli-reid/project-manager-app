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
        $this->handleMutation('created', $setting);
    }

    /**
     * Handle the SettingsSqlite "updated" event
     */
    public function updated(SettingsSqlite $setting): void
    {
        $this->handleMutation('updated', $setting);
    }

    /**
     * Handle the SettingsSqlite "deleted" event
     */
    public function deleted(SettingsSqlite $setting): void
    {
        $this->handleMutation('deleted', $setting);
    }

    /**
     * Handle the SettingsSqlite "restored" event
     */
    public function restored(SettingsSqlite $setting): void
    {
        $this->handleMutation('restored', $setting);
    }

    /**
     * Handle common side effects for a model mutation.
     */
    protected function handleMutation(string $action, SettingsSqlite $setting): void
    {
        $this->logChange($action, $setting);
        $this->clearCaches($setting);
    }

    /**
     * Clear relevant caches after a setting change
     */
    protected function clearCaches(SettingsSqlite $setting): void
    {
        try {
            $cacheKeys = [
                "setting.{$setting->key}",
                "setting.exists.{$setting->key}",
                'settings.all',
                'settings.all.grouped',
                'settings.public',
            ];

            $originalKey = (string) $setting->getOriginal('key');
            if ($originalKey !== '' && $originalKey !== $setting->key) {
                $cacheKeys[] = "setting.{$originalKey}";
                $cacheKeys[] = "setting.exists.{$originalKey}";
            }

            $groupsToClear = array_filter([
                $setting->group,
                $setting->getOriginal('group'),
            ]);

            foreach (array_unique($groupsToClear) as $group) {
                $cacheKeys[] = "settings.group.{$group}";
            }

            $this->cache->forgetMany(array_values(array_unique($cacheKeys)));
        } catch (\Exception $e) {
            Log::warning("Failed to clear settings cache for key '{$setting->key}': ".$e->getMessage());
        }
    }

    /**
     * Log a settings change for audit trail
     */
    protected function logChange(string $action, SettingsSqlite $setting): void
    {
        try {
            $shouldLogChanges = (bool) config('settings-db.log_changes', false);

            if (config('app.debug') || $shouldLogChanges) {
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
