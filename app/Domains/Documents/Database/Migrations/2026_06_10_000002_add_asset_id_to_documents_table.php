<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table): void {
            $table->ulid('asset_id')->nullable()->after('id');
            $table->foreign('asset_id')->references('id')->on('assets')->onDelete('set null');
            $table->index('asset_id');
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table): void {
            $table->dropForeign(['asset_id']);
            $table->dropIndex(['asset_id']);
            $table->dropColumn('asset_id');
        });
    }
};
