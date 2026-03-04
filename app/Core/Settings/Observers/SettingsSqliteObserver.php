<?php

namespace App\Core\Settings\Observers;

use App\Models\SettingsSqlite;
use Illuminate\Support\Facades\Cache;

/**
 * Observer for SettingsSqlite model.
 * Clears cached settings when they are updated or deleted.
 */
class SettingsSqliteObserver
{
    /**
     * Handle the SettingsSqlite "saved" event.
     * Fired after both create and update operations.
     */
    public function saved(SettingsSqlite $setting): void
    {
        $this->clearCache($setting);
    }

    /**
     * Handle the SettingsSqlite "deleted" event.
     */
    public function deleted(SettingsSqlite $setting): void
    {
        $this->clearCache($setting);
    }

    /**
     * Clear the cached setting value.
     */
    protected function clearCache(SettingsSqlite $setting): void
    {
        // Clear the specific setting cache
        $cacheKey = "setting.{$setting->key}";
        Cache::forget($cacheKey);
        
        // Also clear any group-level cache if we implement it later
        if ($setting->group) {
            Cache::forget("settings.group.{$setting->group}");
        }
    }
}
