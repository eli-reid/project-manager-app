<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('plan_links')) {
            return;
        }

        Schema::create('plan_links', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('from_revision_id')->constrained('plan_sheet_revisions')->cascadeOnDelete();
            $table->foreignUlid('target_sheet_id')->constrained('plan_sheets')->cascadeOnDelete();
            $table->json('hotspot');
            $table->string('label')->nullable();
            $table->boolean('auto_detected')->default(false);
            $table->foreignUlid('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index('from_revision_id');
            $table->index('target_sheet_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_links');
    }
};
