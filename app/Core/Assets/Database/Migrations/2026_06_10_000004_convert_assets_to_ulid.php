<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        // Prevent repeated runs from failing: if the temp table exists, stop with guidance
        if (Schema::hasTable('assets_new')) {
            throw new \RuntimeException("Temporary table 'assets_new' already exists. Either rollback the previous migration or drop the 'assets_new' table before re-running this migration.");
        }

        // Create a new table with ULID primary key to copy data into
        Schema::create('assets_new', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('title')->nullable();
            $table->string('original_name');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->string('storage_disk');
            $table->string('storage_path');
            $table->string('folder_path')->nullable();
            // keep created_by as nullable string to accept previous integer values, we'll preserve raw
            $table->string('created_by_id')->nullable();
            $table->timestamps();
        });

        // Build mapping from old numeric id -> new ulid id
        $mapping = [];

        DB::table('assets')->orderBy('id')->chunk(100, function ($rows) use (&$mapping) {
            foreach ($rows as $row) {
                $newId = (string) Str::ulid();

                // Insert into new table preserving columns
                DB::table('assets_new')->insert([
                    'id' => $newId,
                    'title' => $row->title,
                    'original_name' => $row->original_name,
                    'mime_type' => $row->mime_type,
                    'size_bytes' => $row->size_bytes,
                    'storage_disk' => $row->storage_disk,
                    'storage_path' => $row->storage_path,
                    'folder_path' => $row->folder_path,
                    // cast created_by_id to string when present
                    'created_by_id' => isset($row->created_by_id) ? (string) $row->created_by_id : null,
                    'created_at' => $row->created_at,
                    'updated_at' => $row->updated_at,
                ]);

                $mapping[$row->id] = $newId;
            }
        });

        // Add temporary new_asset_id columns to referencing tables (skip if already present)
        if (! Schema::hasColumn('documents', 'new_asset_id')) {
            Schema::table('documents', function (Blueprint $table) {
                $table->ulid('new_asset_id')->nullable()->after('id');
            });
        }

        if (! Schema::hasColumn('project_assets', 'new_asset_id')) {
            Schema::table('project_assets', function (Blueprint $table) {
                $table->ulid('new_asset_id')->nullable()->after('project_id');
            });
        }

        if (! Schema::hasColumn('asset_shares', 'new_asset_id')) {
            Schema::table('asset_shares', function (Blueprint $table) {
                $table->ulid('new_asset_id')->nullable()->after('id');
            });
        }

        // Populate new_asset_id using the mapping
        foreach ($mapping as $old => $new) {
            DB::table('documents')->where('asset_id', $old)->update(['new_asset_id' => $new]);
            DB::table('project_assets')->where('asset_id', $old)->update(['new_asset_id' => $new]);
            DB::table('asset_shares')->where('asset_id', $old)->update(['new_asset_id' => $new]);
        }

        // Determine DB driver so we can use a safe rebuild strategy on SQLite
        $driver = Schema::getConnection()->getDriverName();

        // Swap columns: drop old foreign keys & columns and rename new_asset_id -> asset_id
        // Documents: drop existing foreign key (use information_schema lookup to handle differing constraint names)
        try {
            $fk = DB::selectOne("SELECT CONSTRAINT_NAME as name FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL", [DB::getDatabaseName(), 'documents', 'asset_id']);
            if ($fk && isset($fk->name)) {
                DB::statement("ALTER TABLE `documents` DROP FOREIGN KEY `{$fk->name}`");
            }
        } catch (\Throwable $e) {
            // ignore if constraint not present or query fails
        }

        if ($driver === 'sqlite') {
            // Rebuild `documents` table for SQLite: create new table with ulid asset_id, copy rows, replace table.
            DB::beginTransaction();
            try {
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
                        deleted_at DATETIME
                    );
                SQL);

                // Copy data; prefer new_asset_id when present
                DB::statement(<<<'SQL'
                    INSERT INTO documents_new (
                        id, title, description, original_name, stored_name, extension, mime_type,
                        file_size, storage_disk, storage_path, folder_path, owner_scope, owner_id,
                        visibility, replace_mode, uploaded_by_id, last_replaced_at, created_at, updated_at, deleted_at, asset_id
                    )
                    SELECT
                        id, title, description, original_name, stored_name, extension, mime_type,
                        file_size, storage_disk, storage_path, folder_path, owner_scope, owner_id,
                        visibility, replace_mode, uploaded_by_id, last_replaced_at, created_at, updated_at, deleted_at, new_asset_id
                    FROM documents;
                SQL);

                DB::statement('DROP TABLE documents');
                DB::statement('ALTER TABLE documents_new RENAME TO documents');

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
        } else {
            Schema::table('documents', function (Blueprint $table) {
                if (Schema::hasColumn('documents', 'asset_id')) {
                    $table->dropColumn('asset_id');
                }
            });

            if (! Schema::hasColumn('documents', 'asset_id')) {
                Schema::table('documents', function (Blueprint $table) {
                    $table->ulid('asset_id')->nullable()->after('id');
                });
            }

            // copy new_asset_id -> asset_id
            DB::table('documents')->whereNotNull('new_asset_id')->chunk(100, function ($rows) {
                foreach ($rows as $r) {
                    DB::table('documents')->where('id', $r->id)->update(['asset_id' => $r->new_asset_id]);
                }
            });

            Schema::table('documents', function (Blueprint $table) {
                $table->dropColumn('new_asset_id');
                $table->index('asset_id');
                $table->foreign('asset_id')->references('id')->on('assets_new')->onDelete('set null');
            });
        }

        // Project assets
        try {
            $fk = DB::selectOne("SELECT CONSTRAINT_NAME as name FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL", [DB::getDatabaseName(), 'project_assets', 'asset_id']);
            if ($fk && isset($fk->name)) {
                DB::statement("ALTER TABLE `project_assets` DROP FOREIGN KEY `{$fk->name}`");
            }
        } catch (\Throwable $e) {
        }

        if ($driver === 'sqlite') {
            DB::beginTransaction();
            try {
                DB::statement(<<<'SQL'
                    CREATE TABLE project_assets_new (
                        id TEXT PRIMARY KEY,
                        project_id TEXT NOT NULL,
                        asset_id TEXT,
                        created_by_id TEXT,
                        title TEXT,
                        created_at DATETIME,
                        updated_at DATETIME
                    );
                SQL);

                DB::statement(<<<'SQL'
                    INSERT INTO project_assets_new (id, project_id, asset_id, created_by_id, title, created_at, updated_at)
                    SELECT id, project_id, new_asset_id, created_by_id, title, created_at, updated_at FROM project_assets;
                SQL);

                DB::statement('DROP TABLE project_assets');
                DB::statement('ALTER TABLE project_assets_new RENAME TO project_assets');
                DB::statement('CREATE INDEX project_assets_project_id_index ON project_assets(project_id)');
                DB::statement('CREATE INDEX project_assets_asset_id_index ON project_assets(asset_id)');
                DB::commit();
            } catch (\Throwable $e) {
                DB::rollBack();
                throw $e;
            }
        } else {
            Schema::table('project_assets', function (Blueprint $table) {
                if (Schema::hasColumn('project_assets', 'asset_id')) {
                    $table->dropColumn('asset_id');
                }
            });

            if (! Schema::hasColumn('project_assets', 'asset_id')) {
                Schema::table('project_assets', function (Blueprint $table) {
                    $table->ulid('asset_id')->nullable()->after('project_id');
                });
            }

            DB::table('project_assets')->whereNotNull('new_asset_id')->chunk(100, function ($rows) {
                foreach ($rows as $r) {
                    DB::table('project_assets')->where('id', $r->id)->update(['asset_id' => $r->new_asset_id]);
                }
            });

            Schema::table('project_assets', function (Blueprint $table) {
                $table->dropColumn('new_asset_id');
                $table->index('asset_id');
                $table->foreign('asset_id')->references('id')->on('assets_new')->onDelete('cascade');
            });
        }

        // Asset shares
        try {
            $fk = DB::selectOne("SELECT CONSTRAINT_NAME as name FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL", [DB::getDatabaseName(), 'asset_shares', 'asset_id']);
            if ($fk && isset($fk->name)) {
                DB::statement("ALTER TABLE `asset_shares` DROP FOREIGN KEY `{$fk->name}`");
            }
        } catch (\Throwable $e) {
        }

        if ($driver === 'sqlite') {
            DB::beginTransaction();
            try {
                DB::statement(<<<'SQL'
                    CREATE TABLE asset_shares_new (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        asset_id TEXT,
                        token TEXT UNIQUE,
                        expires_at DATETIME,
                        created_by_id TEXT,
                        created_at DATETIME,
                        updated_at DATETIME
                    );
                SQL);

                DB::statement(<<<'SQL'
                    INSERT INTO asset_shares_new (id, asset_id, token, expires_at, created_by_id, created_at, updated_at)
                    SELECT id, new_asset_id, token, expires_at, created_by_id, created_at, updated_at FROM asset_shares;
                SQL);

                DB::statement('DROP TABLE asset_shares');
                DB::statement('ALTER TABLE asset_shares_new RENAME TO asset_shares');
                DB::statement('CREATE INDEX asset_shares_asset_id_index ON asset_shares(asset_id)');
                DB::commit();
            } catch (\Throwable $e) {
                DB::rollBack();
                throw $e;
            }
        } else {
            Schema::table('asset_shares', function (Blueprint $table) {
                if (Schema::hasColumn('asset_shares', 'asset_id')) {
                    $table->dropColumn('asset_id');
                }
            });

            if (! Schema::hasColumn('asset_shares', 'asset_id')) {
                Schema::table('asset_shares', function (Blueprint $table) {
                    $table->ulid('asset_id')->nullable()->after('id');
                });
            }

            DB::table('asset_shares')->whereNotNull('new_asset_id')->chunk(100, function ($rows) {
                foreach ($rows as $r) {
                    DB::table('asset_shares')->where('id', $r->id)->update(['asset_id' => $r->new_asset_id]);
                }
            });

            Schema::table('asset_shares', function (Blueprint $table) {
                $table->dropColumn('new_asset_id');
                $table->index('asset_id');
                $table->foreign('asset_id')->references('id')->on('assets_new')->onDelete('cascade');
            });
        }

        // Drop old assets table and rename new
        Schema::dropIfExists('assets');
        Schema::rename('assets_new', 'assets');

        // Recreate foreign keys referencing the new assets table to point to 'assets'
        try {
            $fk = DB::selectOne("SELECT CONSTRAINT_NAME as name FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL", [DB::getDatabaseName(), 'documents', 'asset_id']);
            if ($fk && isset($fk->name)) {
                DB::statement("ALTER TABLE `documents` DROP FOREIGN KEY `{$fk->name}`");
            }
        } catch (\Throwable $e) {
        }
        Schema::table('documents', function (Blueprint $table) {
            $table->foreign('asset_id')->references('id')->on('assets')->onDelete('set null');
        });

        try {
            $fk = DB::selectOne("SELECT CONSTRAINT_NAME as name FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL", [DB::getDatabaseName(), 'project_assets', 'asset_id']);
            if ($fk && isset($fk->name)) {
                DB::statement("ALTER TABLE `project_assets` DROP FOREIGN KEY `{$fk->name}`");
            }
        } catch (\Throwable $e) {
        }
        Schema::table('project_assets', function (Blueprint $table) {
            $table->foreign('asset_id')->references('id')->on('assets')->onDelete('cascade');
        });

        try {
            $fk = DB::selectOne("SELECT CONSTRAINT_NAME as name FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL", [DB::getDatabaseName(), 'asset_shares', 'asset_id']);
            if ($fk && isset($fk->name)) {
                DB::statement("ALTER TABLE `asset_shares` DROP FOREIGN KEY `{$fk->name}`");
            }
        } catch (\Throwable $e) {
        }
        Schema::table('asset_shares', function (Blueprint $table) {
            $table->foreign('asset_id')->references('id')->on('assets')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        // Irreversible migration: restoring numeric PKs from ULIDs cannot be safely automated
        throw new \RuntimeException('This migration is irreversible.');
    }
};
