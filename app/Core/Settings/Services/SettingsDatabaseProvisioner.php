<?php

namespace App\Core\Settings\Services;

use Illuminate\Support\Facades\DB;

class SettingsDatabaseProvisioner
{
    /**
     * Track if database has been initialized in this request.
     */
    protected static bool $databaseEnsured = false;

    /**
     * Ensure the SQLite database file, table, and schema all exist.
     */
    public function ensureDatabase(): void
    {
        if (static::$databaseEnsured) {
            return;
        }

        $dbPath = config('settings-db.database_path');

        if (! $dbPath) {
            $dbPath = database_path('settings.sqlite');
        }

        if (! file_exists($dbPath)) {
            $dir = dirname($dbPath);
            if (! is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            touch($dbPath);
            chmod($dbPath, 0644);
        }

        $this->createSettingsTable();
        $this->ensureSchemaParity();

        static::$databaseEnsured = true;
    }

    /**
     * Create the settings table and indexes if they do not already exist.
     */
    protected function createSettingsTable(): void
    {
        $connection = DB::connection('settings_sqlite');

        $tableExists = $connection->select(
            "SELECT name FROM sqlite_master WHERE type='table' AND name='settings'"
        );

        if (! empty($tableExists)) {
            return;
        }

        $connection->statement("
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
        ");

        $connection->statement('CREATE INDEX idx_settings_key ON settings(key)');
        $connection->statement('CREATE INDEX idx_settings_group ON settings(`group`)');
        $connection->statement('CREATE INDEX idx_settings_group_key ON settings(`group`, key)');
        $connection->statement('CREATE INDEX idx_settings_visible ON settings(is_visible)');
    }

    /**
     * Ensure the settings table has all required columns and indexes.
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
