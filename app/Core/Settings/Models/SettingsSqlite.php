<?php

namespace App\Core\Settings\Models;

use App\Traits\EncryptableSettings;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class SettingsSqlite extends Model
{
    use EncryptableSettings, HasUlids;

    /**
     * Track if database has been initialized in this request
     */
    protected static bool $databaseEnsured = false;

    /**
     * The connection name for the model.
     */
    protected $connection = 'settings_sqlite';

    /**
     * The table associated with the model.
     */
    protected $table = 'settings';

    /**
     * The primary key for the model.
     */
    protected $primaryKey = 'id';

    /**
     * Indicates if the model should be timestamped.
     */
    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'key',
        'value',
        'default_value',
        'display_name',
        'description',
        'type',
        'group',
        'options',
        'order',
        'is_public',
        'is_visible',
        'is_required',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'options' => 'array',
        'is_public' => 'boolean',
        'is_visible' => 'boolean',
        'is_required' => 'boolean',
        'encrypted' => 'boolean',
        'order' => 'integer',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        // Ensure the SQLite database and table exist
        static::creating(function ($model) {
            $model->ensureSettingsDatabase();
        });
    }

    /**
     * Ensure the settings SQLite database and table exist
     */
    public function ensureSettingsDatabase(): void
    {
        // Skip if already ensured in this request
        if (static::$databaseEnsured) {
            return;
        }

        $dbPath = config('settings-db.database_path');
        
        if (!$dbPath) {
            $dbPath = database_path('settings.sqlite');
        }
        
        // Create database file if it doesn't exist
        if (!file_exists($dbPath)) {
            // Ensure directory exists
            $dir = dirname($dbPath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            
            // Create empty database file
            touch($dbPath);
            chmod($dbPath, 0644);
        }

        // Create table if it doesn't exist (only runs once per request now)
        $this->createSettingsTable();
        
        // Mark as ensured for this request
        static::$databaseEnsured = true;
    }

    /**
     * Create the settings table in SQLite
     * Only creates table and indexes if they don't already exist
     */
    protected function createSettingsTable(): void
    {
        $connection = DB::connection('settings_sqlite');
        
        // Check if table exists first to avoid unnecessary queries
        $tableExists = $connection->select(
            "SELECT name FROM sqlite_master WHERE type='table' AND name='settings'"
        );
        
        if (empty($tableExists)) {
            // Table doesn't exist, create it
            $sql = "
                CREATE TABLE settings (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    key TEXT UNIQUE NOT NULL,
                    value TEXT,
                    default_value TEXT,
                    display_name TEXT,
                    description TEXT,
                    type TEXT DEFAULT 'text',
                    `group` TEXT DEFAULT 'general',
                    options TEXT,
                    `order` INTEGER DEFAULT 0,
                    is_public BOOLEAN DEFAULT 0,
                    is_visible BOOLEAN DEFAULT 1,
                    is_required BOOLEAN DEFAULT 0,
                    created_at DATETIME,
                    updated_at DATETIME
                )
            ";
            
            $connection->statement($sql);
            
            // Create indexes
            $connection->statement("CREATE INDEX idx_settings_key ON settings(key)");
            $connection->statement("CREATE INDEX idx_settings_group ON settings(`group`)");
            $connection->statement("CREATE INDEX idx_settings_group_key ON settings(`group`, key)");
        }
    }

    /**
     * Get a setting value by key with permanent caching
     */
    public static function getValue(string $key, mixed $default = null): mixed
    {
        try {
            $cacheKey = "setting.{$key}";
            
            return cache()->rememberForever($cacheKey, function () use ($key, $default) {
                $instance = new static();
                $instance->ensureSettingsDatabase();
                
                $setting = static::where('key', $key)->first();
                
                if ($setting && $setting->value !== null) {
                    return $setting->value; // Uses accessor with decryption
                }
                
                // Fallback to .env
                return static::getEnvFallback($key, $default);
            });
            
        } catch (\Exception $e) {
            if (app()->bound('log')) {
                Log::warning("Settings SQLite error for key '{$key}': " . $e->getMessage());
            }
            return static::getEnvFallback($key, $default);
        }
    }

    /**
     * Set a setting value
     */
    public static function setValue(string $key, mixed $value, array $attributes = []): bool
    {
        try {
            $instance = new static();
            $instance->ensureSettingsDatabase();
            
            $setting = static::where('key', $key)->first();
            
            if (!$setting) {
                $setting = new static();
                $setting->key = $key;
                $setting->display_name = $attributes['display_name'] ?? ucwords(str_replace('_', ' ', $key));
                $setting->description = $attributes['description'] ?? '';
                $setting->type = $attributes['type'] ?? static::determineType($value);
                $setting->group = $attributes['group'] ?? static::determineGroup($key);
                $setting->options = $attributes['options'] ?? null; // Store dropdown options
                $setting->order = $attributes['order'] ?? 0;
                $setting->is_public = $attributes['is_public'] ?? false;
                $setting->is_visible = $attributes['is_visible'] ?? true;
                $setting->is_required = $attributes['is_required'] ?? false;
                $setting->default_value = $attributes['default_value'] ?? $value; // Store default value
                $setting->encrypted = $attributes['encrypted'] ?? ($attributes['type'] ?? null) === 'password' ? 1 : 0;
            } else {
                // Update metadata if provided (but preserve existing values if not)
                if (isset($attributes['display_name'])) $setting->display_name = $attributes['display_name'];
                if (isset($attributes['description'])) $setting->description = $attributes['description'];
                if (isset($attributes['type'])) $setting->type = $attributes['type'];
                if (isset($attributes['group'])) $setting->group = $attributes['group'];
                if (isset($attributes['options'])) $setting->options = $attributes['options']; // Update dropdown options
                if (isset($attributes['order'])) $setting->order = $attributes['order'];
                if (isset($attributes['is_public'])) $setting->is_public = $attributes['is_public'];
                if (isset($attributes['is_visible'])) $setting->is_visible = $attributes['is_visible'];
                if (isset($attributes['is_required'])) $setting->is_required = $attributes['is_required'];
                if (isset($attributes['default_value'])) $setting->default_value = $attributes['default_value'];
                if (isset($attributes['encrypted'])) $setting->encrypted = $attributes['encrypted'];
            }
            
            $setting->value = $value; // Uses mutator with encryption
            return $setting->save();
            
        } catch (\Exception $e) {
            \Log::error("Failed to save setting '{$key}': " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get .env fallback value
     */
    protected static function getEnvFallback(string $key, mixed $default = null): mixed
    {
        $envMappings = config('settings-db.env_mappings', []);
        
        if (isset($envMappings[$key])) {
            $envKey = $envMappings[$key];
            $value = env($envKey);
            
            if ($value !== null) {
                // Handle type conversion
                if (in_array($key, ['app_debug']) && is_string($value)) {
                    return filter_var($value, FILTER_VALIDATE_BOOLEAN);
                }
                
                if (in_array($key, ['db_port', 'mail_port', 'redis_port', 'session_lifetime']) && is_string($value)) {
                    return (int) $value;
                }
                
                return $value;
            }
        }
        
        return $default;
    }

    /**
     * Determine setting group from key
     */
    protected static function determineGroup(string $key): string
    {
        $groupMappings = [
            'app_' => 'general',
            'company_' => 'company',
            'db_' => 'database',
            'mail_' => 'mail',
            'timecard_' => 'timecards',
            'default_' => 'localization',
            'date_' => 'localization',
            'time_' => 'localization',
            'currency' => 'financial',
            'decimal_' => 'financial',
            'cache_' => 'performance',
            'queue_' => 'performance',
            'session_' => 'security',
            'log_' => 'system',
            'redis_' => 'system',
            'pusher_' => 'system',
            'aws_' => 'system',
        ];

        foreach ($groupMappings as $prefix => $group) {
            if (str_starts_with($key, $prefix)) {
                return $group;
            }
        }

        return 'general';
    }

    /**
     * Determine setting type from value
     */
    protected static function determineType(mixed $value): string
    {
        if (is_bool($value)) {
            return 'boolean';
        }
        
        if (is_numeric($value)) {
            return 'number';
        }
        
        if (is_string($value)) {
            if (filter_var($value, FILTER_VALIDATE_EMAIL)) {
                return 'email';
            }
            
            if (filter_var($value, FILTER_VALIDATE_URL)) {
                return 'url';
            }
            
            if (str_contains(strtolower($value), 'password') || str_contains(strtolower($value), 'secret')) {
                return 'password';
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
    public static function getAllSettings(): array
    {
        try {
            $instance = new static();
            $instance->ensureSettingsDatabase();
            
            return static::all()->pluck('value', 'key')->toArray();
        } catch (\Exception $e) {
            \Log::warning("Failed to get all settings: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Clear cache for a specific setting
     */
    public static function clearCache(string $key): void
    {
        $cacheKey = "setting.{$key}";
        cache()->forget($cacheKey);
    }

    /**
     * Clear all settings cache
     * Useful for debugging or after bulk updates
     */
    public static function clearAllCache(): void
    {
        try {
            $instance = new static();
            $instance->ensureSettingsDatabase();
            
            $allSettings = static::all();
            
            foreach ($allSettings as $setting) {
                static::clearCache($setting->key);
            }
            
            // Also clear group caches
            $groups = static::pluck('group')->unique();
            foreach ($groups as $group) {
                cache()->forget("settings.group.{$group}");
            }
        } catch (\Exception $e) {
            \Log::warning("Failed to clear all settings cache: " . $e->getMessage());
        }
    }
}
