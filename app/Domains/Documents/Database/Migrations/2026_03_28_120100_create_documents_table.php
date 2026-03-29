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
                $table->string('owner_scope', 20)->default('user');
                $table->string('visibility', 20)->default('private');
                $table->string('replace_mode', 20)->default('replace');
                $table->foreignUlid('uploaded_by_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('last_replaced_at')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['owner_scope', 'visibility']);
                $table->index(['uploaded_by_id', 'visibility']);
                $table->index('created_at');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
