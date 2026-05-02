<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        // Step 1: Add owner_id column if it doesn't exist
        if (! Schema::hasColumn('documents', 'owner_id')) {
            Schema::table('documents', function (Blueprint $table) {
                $table->char('owner_id', 26)->nullable()->after('storage_path');
            });
        }

        // Step 2: Only backfill if the old pivot tables exist (handles fresh installs)
        if (! Schema::hasTable('document_user_owners') && ! Schema::hasTable('document_project_owners')) {
            // Fresh install - no data to migrate
            // Set default owner for any existing documents without an owner
            DB::table('documents')
                ->whereNull('owner_id')
                ->update(['owner_scope' => 'user']);

            return;
        }

        // Step 3: Backfill owner_id from user owners pivot (highest priority) if table exists
        if (Schema::hasTable('document_user_owners')) {
            if ($driver === 'sqlite') {
                DB::statement('
                    UPDATE documents
                    SET owner_scope = ?, owner_id = (
                        SELECT user_id FROM document_user_owners
                        WHERE document_user_owners.document_id = documents.id
                        LIMIT 1
                    )
                    WHERE owner_id IS NULL
                    AND EXISTS (
                        SELECT 1 FROM document_user_owners
                        WHERE document_user_owners.document_id = documents.id
                    )
                ', ['user']);
            } else {
                DB::table('documents as d')
                    ->whereNull('d.owner_id')
                    ->join('document_user_owners as duo', 'd.id', '=', 'duo.document_id')
                    ->update([
                        'd.owner_scope' => DB::raw("'user'"),
                        'd.owner_id' => DB::raw('duo.user_id'),
                    ]);
            }
        }

        // Step 4: Backfill owner_id from project owners pivot if table exists
        if (Schema::hasTable('document_project_owners')) {
            if ($driver === 'sqlite') {
                DB::statement('
                    UPDATE documents
                    SET owner_scope = ?, owner_id = (
                        SELECT project_id FROM document_project_owners
                        WHERE document_project_owners.document_id = documents.id
                        LIMIT 1
                    )
                    WHERE owner_id IS NULL
                    AND EXISTS (
                        SELECT 1 FROM document_project_owners
                        WHERE document_project_owners.document_id = documents.id
                    )
                ', ['project']);
            } else {
                DB::table('documents as d')
                    ->whereNull('d.owner_id')
                    ->join('document_project_owners as dpo', 'd.id', '=', 'dpo.document_id')
                    ->update([
                        'd.owner_scope' => DB::raw("'project'"),
                        'd.owner_id' => DB::raw('dpo.project_id'),
                    ]);
            }
        }

        // Step 5: Assign default owner for any remaining orphaned documents
        $orphanedCount = DB::table('documents')->whereNull('owner_id')->count();

        if ($orphanedCount > 0) {
            // For safety, assign them as user-owned (or log for manual review)
            DB::table('documents')
                ->whereNull('owner_id')
                ->update([
                    'owner_scope' => 'user',
                    'owner_id' => DB::raw('COALESCE(uploaded_by_id, (SELECT id FROM users LIMIT 1))'),
                ]);

            \Log::warning(
                "Document ownership migration: {$orphanedCount} orphaned documents were assigned a default owner. "
                .'Check admin panel for documents without proper ownership.'
            );
        }

        // Step 6: Check for XOR violations (documents with both user and project owners)
        // This indicates data corruption under the old system
        if (Schema::hasTable('document_user_owners') && Schema::hasTable('document_project_owners')) {
            if ($driver === 'sqlite') {
                $xorViolations = DB::select('
                    SELECT COUNT(*) as count FROM documents d
                    WHERE EXISTS (
                        SELECT 1 FROM document_user_owners duo WHERE duo.document_id = d.id
                    )
                    AND EXISTS (
                        SELECT 1 FROM document_project_owners dpo WHERE dpo.document_id = d.id
                    )
                ')[0]->count ?? 0;
            } else {
                $xorViolations = DB::table('documents as d')
                    ->join('document_user_owners as duo', 'd.id', '=', 'duo.document_id')
                    ->join('document_project_owners as dpo', 'd.id', '=', 'dpo.document_id')
                    ->count();
            }

            if ($xorViolations > 0) {
                \Log::warning(
                    "Document ownership integrity issue detected: {$xorViolations} documents have both user and project owners. "
                    .'These were resolved by assigning user ownership. Review in admin panel if needed.'
                );
            }
        }

        // Step 7: Make owner_id NOT NULL for new schema consistency
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE documents MODIFY owner_id CHAR(26) NOT NULL');
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE documents ALTER COLUMN owner_id SET NOT NULL');
        } elseif ($driver === 'sqlite') {
            // SQLite: Column will remain effectively NOT NULL since all rows are populated
            // No need for explicit NOT NULL constraint modification in SQLite
        }

        // Step 8: Create indexes for new query patterns (if not already present)
        if (! $this->indexExists('documents', 'documents_owner_scope_owner_id_index')) {
            Schema::table('documents', function (Blueprint $table) {
                $table->index(['owner_scope', 'owner_id']);
            });
        }

        // Step 9: Log migration completion
        \Log::info(
            'Document ownership migration completed successfully. '
            .DB::table('documents')->count().' documents migrated to new ownership model.'
        );
    }

    public function down(): void
    {
        $driver = DB::connection()->getDriverName();

        // Step 1: Drop the indexes we added
        if ($this->indexExists('documents', 'documents_owner_scope_owner_id_index')) {
            Schema::table('documents', function (Blueprint $table) {
                $table->dropIndex('documents_owner_scope_owner_id_index');
            });
        }

        // Step 2: Make owner_id nullable again for rollback
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE documents MODIFY owner_id CHAR(26) NULL');
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE documents ALTER COLUMN owner_id DROP NOT NULL');
        }

        // Step 3: Clear owner_id values
        DB::table('documents')->update(['owner_id' => null]);

        // Step 4: Log rollback
        \Log::info('Document ownership migration rolled back.');
    }

    /**
     * Check if an index exists on a table.
     */
    private function indexExists(string $table, string $indexName): bool
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            return collect(DB::select(
                "SELECT name FROM sqlite_master WHERE type='index' AND tbl_name=? AND name=?",
                [$table, $indexName]
            ))->isNotEmpty();
        }

        if ($driver === 'mysql') {
            return collect(DB::select(
                'SELECT INDEX_NAME FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_NAME=? AND INDEX_NAME=?',
                [$table, $indexName]
            ))->isNotEmpty();
        }

        if ($driver === 'pgsql') {
            return collect(DB::select(
                "SELECT indexname FROM pg_indexes WHERE tablename=? AND indexname=?",
                [$table, $indexName]
            ))->isNotEmpty();
        }

        return false;
    }
};
