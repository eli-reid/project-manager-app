<?php

namespace App\Core\Settings\Repositories;

use App\Core\Settings\Models\SettingsSqlite;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use App\Core\Settings\DTO\Setting;
use App\Core\Settings\DTO\SettingType;
Use App\Core\Settings\DTO\SettingFormFieldType;

use Illuminate\Support\Str;

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

    public function saveSetting(Setting $setting): ?SettingsSqlite
    {
        return $this->save(
            key: $setting->key,
            value: $setting->value,
            attributes: [
                'display_name' => $setting->display_name,
                'description' => $setting->description,
                'type' => $setting->type,
                'group' => $setting->group,
                'options' => $setting->options,
                'order' => $setting->order,
                'is_public' => $setting->is_public,
                'is_visible' => $setting->is_visible,
                'is_required' => $setting->is_required,
                'encrypted' => $setting->encrypted,
                'default_value' => '',
            ]
        );
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
                $attributes = $this->withDefaultMetadata($key, $value, $attributes);
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
     * Add safe defaults for new setting records.
     */
    private function withDefaultMetadata(string $key, mixed $value, array $attributes): array
    {
        $group = $attributes['group'] ?? $this->groupFromKey($key);

        return [
            'display_name' => $attributes['display_name'] ?? (string) Str::of($key)
                ->afterLast('.')
                ->replace(['_', '-'], ' ')
                ->headline(),
            'description' => $attributes['description'] ?? '',
            'type' => $attributes['type'] ?? $this->inferType($value),
            'group' => $group,
            'options' => $attributes['options'] ?? null,
            'order' => $attributes['order'] ?? 0,
            'is_public' => $attributes['is_public'] ?? false,
            'is_visible' => $attributes['is_visible'] ?? true,
            'is_required' => $attributes['is_required'] ?? false,
            'encrypted' => $attributes['encrypted'] ?? false,
            'default_value' => $attributes['default_value'] ?? $value,
            ...$attributes,
        ];
    }

    private function groupFromKey(string $key): string
    {
        if (! str_contains($key, '.')) {
            return 'general';
        }

        $group = (string) Str::of($key)->before('.')->trim();

        return $group !== '' ? $group : 'general';
    }

    private function inferType(mixed $value): string
    {
        if (is_bool($value)) {
            return 'boolean';
        }

        if (is_numeric($value)) {
            return 'number';
        }

        if (is_array($value)) {
            return 'array';
        }

        if (is_string($value)) {
            if (filter_var($value, FILTER_VALIDATE_EMAIL)) {
                return 'email';
            }

            if (filter_var($value, FILTER_VALIDATE_URL)) {
                return 'url';
            }

            if (strlen($value) > 255) {
                return 'textarea';
            }
        }

        return 'text';
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
     * Find multiple settings by keys.
     *
     * @param  array<int, string>  $keys
     * @return EloquentCollection<int, SettingsSqlite>
     */
    public function findMany(array $keys): EloquentCollection
    {
        try {
            if ($keys === []) {
                return new EloquentCollection;
            }

            return SettingsSqlite::query()
                ->whereIn('key', $keys)
                ->get();
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
