<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('documents')) {
            Schema::create('documents', function (Blueprint $table): void {
                $table->ulid('id')->primary();
                $table->string('title');
                $table->text('description')->nullable();
                $table->string('original_name');
                $table->string('stored_name');
                $table->string('extension', 20)->nullable();
                $table->string('mime_type', 120);
                $table->unsignedBigInteger('file_size');
                $table->string('storage_disk', 30)->default('local');
                $table->string('storage_path');
                $table->string('folder_path')->nullable();
                $table->string('owner_scope', 20)->default('user');
                $table->char('owner_id', 26);
                $table->string('visibility', 20)->default('private');
                $table->string('replace_mode', 20)->default('replace');
                $table->foreignUlid('uploaded_by_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('last_replaced_at')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['owner_scope', 'owner_id']);
                $table->index(['owner_scope', 'visibility']);
                $table->index(['owner_scope', 'owner_id', 'folder_path']);
                $table->index(['uploaded_by_id', 'visibility']);
                $table->index('created_at');
            });
        } else {
            // Ensure owner_id column exists (handles case where table was created before this column was added)
            if (! Schema::hasColumn('documents', 'owner_id')) {
                Schema::table('documents', function (Blueprint $table): void {
                    $table->char('owner_id', 26)->nullable()->after('storage_path');
                    $table->index(['owner_scope', 'owner_id']);
                });
            }

            if (! Schema::hasColumn('documents', 'folder_path')) {
                Schema::table('documents', function (Blueprint $table): void {
                    $table->string('folder_path')->nullable()->after('storage_path');
                    $table->index(['owner_scope', 'owner_id', 'folder_path']);
                });
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
