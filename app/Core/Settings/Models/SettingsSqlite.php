<?php

namespace App\Core\Settings\Models;

use App\Core\Settings\Traits\EncryptableSettings;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class SettingsSqlite extends Model
{
    use EncryptableSettings;

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
     * The "type" of the primary key ID.
     */
    protected $keyType = 'int';

    /**
     * Indicates if the IDs are auto-incrementing.
     */
    public $incrementing = true;

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
        'encrypted',
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

        if (! $dbPath) {
            $dbPath = database_path('settings.sqlite');
        }

        // Create database file if it doesn't exist
        if (! file_exists($dbPath)) {
            // Ensure directory exists
            $dir = dirname($dbPath);
            if (! is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            // Create empty database file
            touch($dbPath);
            chmod($dbPath, 0644);
        }

        // Create table if it doesn't exist (only runs once per request now)
        $this->createSettingsTable();

        // Ensure existing tables are brought up to expected schema.
        $this->ensureSchemaParity();

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
                    encrypted BOOLEAN DEFAULT 0,
                    created_at DATETIME,
                    updated_at DATETIME
                )
            ";

            $connection->statement($sql);

            // Create indexes
            $connection->statement('CREATE INDEX idx_settings_key ON settings(key)');
            $connection->statement('CREATE INDEX idx_settings_group ON settings(`group`)');
            $connection->statement('CREATE INDEX idx_settings_group_key ON settings(`group`, key)');
            $connection->statement('CREATE INDEX idx_settings_visible ON settings(is_visible)');
        }
    }

    /**
     * Ensure settings table has required columns/indexes for schema parity.
     */
    protected function ensureSchemaParity(): void
    {
        $connection = DB::connection('settings_sqlite');

        $columns = $connection->select("PRAGMA table_info('settings')");
        $columnNames = array_map(static fn ($column) => $column->name ?? null, $columns);

        if (! in_array('encrypted', $columnNames, true)) {
            $connection->statement('ALTER TABLE settings ADD COLUMN encrypted BOOLEAN DEFAULT 0');
        }

        $indexes = $connection->select("PRAGMA index_list('settings')");
        $indexNames = array_map(static fn ($index) => $index->name ?? null, $indexes);

        if (! in_array('idx_settings_visible', $indexNames, true)) {
            $connection->statement('CREATE INDEX idx_settings_visible ON settings(is_visible)');
        }
    }
}
