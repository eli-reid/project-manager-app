<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('documents', 'folder_path')) {
            Schema::table('documents', function (Blueprint $table): void {
                $table->string('folder_path')->nullable()->after('storage_path');
                $table->index(['owner_scope', 'owner_id', 'folder_path']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('documents', 'folder_path')) {
            Schema::table('documents', function (Blueprint $table): void {
                $table->dropIndex(['owner_scope', 'owner_id', 'folder_path']);
                $table->dropColumn('folder_path');
            });
        }
    }
};
