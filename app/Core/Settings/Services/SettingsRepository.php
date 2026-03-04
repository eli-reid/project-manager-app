<?php

namespace App\Core\Settings\Services;

use App\Core\Settings\Models\SettingsSqlite;

/**
 * SettingsRepository
 * 
 * Pure data access layer for settings. No business logic, no caching, no fallbacks.
 * Sole responsibility: fetch and persist setting records to the database.
 */
class SettingsRepository
{
    /**
     * Find a setting by key
     *
     * @param string $key
     * @return SettingsSqlite|null
     */
    public function find(string $key): ?SettingsSqlite
    {
        try {
            return SettingsSqlite::where('key', $key)->first();
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Save a setting
     *
     * @param string $key
     * @param mixed $value
     * @param array $attributes
     * @return SettingsSqlite|null
     */
    public function save(string $key, mixed $value, array $attributes = []): ?SettingsSqlite
    {
        try {
            $setting = $this->find($key);

            if (!$setting) {
                $setting = new SettingsSqlite();
                $setting->key = $key;
            }

            // Apply provided attributes
            foreach ($attributes as $attrKey => $attrValue) {
                if ($setting->isFillable($attrKey)) {
                    $setting->{$attrKey} = $attrValue;
                }
            }

            $setting->value = $value;
            $setting->save();

            return $setting;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get all settings
     *
     * @param string|null $group
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function all(?string $group = null): \Illuminate\Database\Eloquent\Collection
    {
        try {
            $query = SettingsSqlite::query();

            if ($group) {
                $query->where('group', $group);
            }

            return $query->get();
        } catch (\Exception $e) {
            return collect();
        }
    }

    /**
     * Delete a setting by key
     *
     * @param string $key
     * @return bool
     */
    public function delete(string $key): bool
    {
        try {
            $setting = $this->find($key);

            if ($setting) {
                return $setting->delete();
            }

            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check if a setting exists
     *
     * @param string $key
     * @return bool
     */
    public function exists(string $key): bool
    {
        try {
            return SettingsSqlite::where('key', $key)->exists();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get all settings as key-value pairs
     *
     * @return array
     */
    public function toArray(): array
    {
        try {
            return SettingsSqlite::pluck('value', 'key')->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }
}
