<?php

namespace App\Core\Settings\Repositories;

use App\Core\Settings\Models\SettingsSqlite;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

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
     */
    public function save(string $key, mixed $value, array $attributes = []): ?SettingsSqlite
    {
        try {
            $setting = $this->find($key);

            if (! $setting) {
                $setting = new SettingsSqlite;
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
     */
    public function all(?string $group = null): EloquentCollection
    {
        try {
            $query = SettingsSqlite::query();

            if ($group) {
                $query->where('group', $group);
            }

            return $query->get();
        } catch (\Exception $e) {
            return new EloquentCollection;
        }
    }

    /**
     * Delete a setting by key
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
