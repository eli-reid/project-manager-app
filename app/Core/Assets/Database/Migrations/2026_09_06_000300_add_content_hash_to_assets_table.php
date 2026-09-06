<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('assets') || Schema::hasColumn('assets', 'content_hash')) {
            return;
        }

        Schema::table('assets', function (Blueprint $table): void {
            $table->string('content_hash', 64)->nullable()->after('folder_path');
            $table->index(['storage_disk', 'content_hash']);
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('assets') || ! Schema::hasColumn('assets', 'content_hash')) {
            return;
        }

        Schema::table('assets', function (Blueprint $table): void {
            $table->dropIndex(['storage_disk', 'content_hash']);
            $table->dropColumn('content_hash');
        });
    }
};
