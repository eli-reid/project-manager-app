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

        // Add temporary new_asset_id columns to referencing tables
        Schema::table('documents', function (Blueprint $table) {
            $table->ulid('new_asset_id')->nullable()->after('id');
        });

        Schema::table('project_assets', function (Blueprint $table) {
            $table->ulid('new_asset_id')->nullable()->after('project_id');
        });

        Schema::table('asset_shares', function (Blueprint $table) {
            $table->ulid('new_asset_id')->nullable()->after('id');
        });

        // Populate new_asset_id using the mapping
        foreach ($mapping as $old => $new) {
            DB::table('documents')->where('asset_id', $old)->update(['new_asset_id' => $new]);
            DB::table('project_assets')->where('asset_id', $old)->update(['new_asset_id' => $new]);
            DB::table('asset_shares')->where('asset_id', $old)->update(['new_asset_id' => $new]);
        }

        // Swap columns: drop old foreign keys & columns and rename new_asset_id -> asset_id
        // Documents
        Schema::table('documents', function (Blueprint $table) {
            // drop foreign if exists
            try {
                $table->dropForeign(['asset_id']);
            } catch (\Throwable $e) {
                // ignore if constraint not present
            }
        });

        Schema::table('documents', function (Blueprint $table) {
            if (Schema::hasColumn('documents', 'asset_id')) {
                $table->dropColumn('asset_id');
            }
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->ulid('asset_id')->nullable()->after('id');
        });

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

        // Project assets
        Schema::table('project_assets', function (Blueprint $table) {
            try {
                $table->dropForeign(['asset_id']);
            } catch (\Throwable $e) {
            }
        });

        Schema::table('project_assets', function (Blueprint $table) {
            if (Schema::hasColumn('project_assets', 'asset_id')) {
                $table->dropColumn('asset_id');
            }
        });

        Schema::table('project_assets', function (Blueprint $table) {
            $table->ulid('asset_id')->nullable()->after('project_id');
        });

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

        // Asset shares
        Schema::table('asset_shares', function (Blueprint $table) {
            try {
                $table->dropForeign(['asset_id']);
            } catch (\Throwable $e) {
            }
        });

        Schema::table('asset_shares', function (Blueprint $table) {
            if (Schema::hasColumn('asset_shares', 'asset_id')) {
                $table->dropColumn('asset_id');
            }
        });

        Schema::table('asset_shares', function (Blueprint $table) {
            $table->ulid('asset_id')->nullable()->after('id');
        });

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

        // Drop old assets table and rename new
        Schema::dropIfExists('assets');
        Schema::rename('assets_new', 'assets');

        // Recreate foreign keys referencing the new assets table to point to 'assets'
        Schema::table('documents', function (Blueprint $table) {
            try {
                $table->dropForeign(['asset_id']);
            } catch (\Throwable $e) {
            }
            $table->foreign('asset_id')->references('id')->on('assets')->onDelete('set null');
        });

        Schema::table('project_assets', function (Blueprint $table) {
            try {
                $table->dropForeign(['asset_id']);
            } catch (\Throwable $e) {
            }
            $table->foreign('asset_id')->references('id')->on('assets')->onDelete('cascade');
        });

        Schema::table('asset_shares', function (Blueprint $table) {
            try {
                $table->dropForeign(['asset_id']);
            } catch (\Throwable $e) {
            }
            $table->foreign('asset_id')->references('id')->on('assets')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        // Irreversible migration: restoring numeric PKs from ULIDs cannot be safely automated
        throw new \RuntimeException('This migration is irreversible.');
    }
};
