<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
            if (! Schema::hasColumn('documents', 'asset_id')) {
                Schema::table('documents', function (Blueprint $table): void {
                    $table->unsignedBigInteger('asset_id')->nullable()->after('id');
                    $table->foreign('asset_id')->references('id')->on('assets')->onDelete('set null');
                    $table->index('asset_id');
                });
            }
    }

    public function down(): void
    {
            // SQLite has limited ALTER TABLE support (cannot drop columns or foreign keys cleanly).
            // To avoid errors during rollback on SQLite (dev/test), skip the destructive operations.
            $driver = Schema::getConnection()->getDriverName();

            if ($driver === 'sqlite') {
                // Skip drop on SQLite to avoid "unknown column" errors; manual cleanup or full rebuild required.
                return;
            }

            if (Schema::hasColumn('documents', 'asset_id')) {
                try {
                    Schema::table('documents', function (Blueprint $table): void {
                        $table->dropForeign(['asset_id']);
                        $table->dropIndex(['asset_id']);
                        $table->dropColumn('asset_id');
                    });
                } catch (\Throwable $e) {
                    // If the foreign/index/column was already removed or differs, ignore to keep rollback safe.
                }
            }
    }
};
