<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('assets')) {
            return;
        }

        Schema::create('assets', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('original_name');
            $table->string('mime_type', 120)->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->string('storage_disk', 30);
            $table->string('storage_path');
            $table->string('folder_path')->nullable();
            $table->string('content_hash', 64)->nullable();
            $table->foreignUlid('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // Indexed rather than unique: deduplication is opt-out per upload, so
            // several assets may legitimately share content on the same disk.
            $table->index(['storage_disk', 'content_hash']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
