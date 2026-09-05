<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasColumn('documents', 'asset_id')) {
            return;
        }

        Schema::table('documents', function (Blueprint $table): void {
            $table->ulid('asset_id')->nullable()->after('id');
            $table->foreign('asset_id')->references('id')->on('assets')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('documents', 'asset_id')) {
            return;
        }

        Schema::table('documents', function (Blueprint $table): void {
            $table->dropForeign(['asset_id']);
            $table->dropColumn('asset_id');
        });
    }
};
