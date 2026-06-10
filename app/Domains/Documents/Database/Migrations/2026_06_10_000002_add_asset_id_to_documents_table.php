<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table): void {
            $table->foreignUlid('asset_id')->nullable()->after('id')->constrained('assets')->nullOnDelete();
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
