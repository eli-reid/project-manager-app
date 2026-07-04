<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        // Only run this safe-rebuild on SQLite where dropping columns is not supported.
        if ($driver !== 'sqlite') {
            return;
        }

        // If there's no asset_id, nothing to do.
        if (! Schema::hasColumn('documents', 'asset_id')) {
            return;
        }

        DB::beginTransaction();
        try {
            // Create a new temporary table without the asset_id column.
            DB::statement(<<<'SQL'
                CREATE TABLE documents_new (
                    id TEXT PRIMARY KEY,
                    title TEXT NOT NULL,
                    description TEXT,
                    original_name TEXT NOT NULL,
                    stored_name TEXT NOT NULL,
                    extension TEXT,
                    mime_type TEXT NOT NULL,
                    file_size INTEGER NOT NULL,
                    storage_disk TEXT NOT NULL,
                    storage_path TEXT NOT NULL,
                    folder_path TEXT,
                    owner_scope TEXT NOT NULL,
                    owner_id TEXT,
                    visibility TEXT NOT NULL,
                    replace_mode TEXT NOT NULL,
                    uploaded_by_id TEXT,
                    last_replaced_at DATETIME,
                    created_at DATETIME,
                    updated_at DATETIME,
                    deleted_at DATETIME,
                    FOREIGN KEY(uploaded_by_id) REFERENCES users(id) ON DELETE SET NULL
                );
            SQL);

            // Copy data from the old table into the new table (omit asset_id).
            DB::statement(<<<'SQL'
                INSERT INTO documents_new (
                    id, title, description, original_name, stored_name, extension, mime_type,
                    file_size, storage_disk, storage_path, folder_path, owner_scope, owner_id,
                    visibility, replace_mode, uploaded_by_id, last_replaced_at, created_at, updated_at, deleted_at
                )
                SELECT
                    id, title, description, original_name, stored_name, extension, mime_type,
                    file_size, storage_disk, storage_path, folder_path, owner_scope, owner_id,
                    visibility, replace_mode, uploaded_by_id, last_replaced_at, created_at, updated_at, deleted_at
                FROM documents;
            SQL);

            // Drop the old table and rename the new one.
            DB::statement('DROP TABLE documents');
            DB::statement('ALTER TABLE documents_new RENAME TO documents');

            // Recreate indexes that existed on the original table.
            DB::statement('CREATE INDEX documents_owner_scope_owner_id_index ON documents(owner_scope, owner_id)');
            DB::statement('CREATE INDEX documents_owner_scope_visibility_index ON documents(owner_scope, visibility)');
            DB::statement('CREATE INDEX documents_owner_scope_owner_id_folder_path_index ON documents(owner_scope, owner_id, folder_path)');
            DB::statement('CREATE INDEX documents_uploaded_by_id_visibility_index ON documents(uploaded_by_id, visibility)');
            DB::statement('CREATE INDEX documents_created_at_index ON documents(created_at)');

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function down(): void
    {
        // This operation is intentionally irreversible for SQLite in-place.
        // Restoring the dropped `asset_id` column requires table rebuild from backup or full DB migration.
        return;
    }
};
