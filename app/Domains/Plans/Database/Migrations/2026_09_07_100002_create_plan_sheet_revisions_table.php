<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('plan_sheet_revisions')) {
            return;
        }

        Schema::create('plan_sheet_revisions', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('plan_sheet_id')->constrained('plan_sheets')->cascadeOnDelete();
            $table->foreignUlid('plan_set_id')->constrained('plan_sets')->cascadeOnDelete();
            $table->string('revision_label')->nullable();
            $table->unsignedInteger('page_number');
            $table->string('thumbnail_path')->nullable();
            $table->string('preview_path')->nullable();
            $table->json('tile_manifest')->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->smallInteger('rotation')->default(0);
            $table->longText('text_layer')->nullable();
            $table->string('detected_sheet_number')->nullable();
            $table->string('detected_title')->nullable();
            $table->decimal('detection_confidence', 5, 4)->nullable();
            $table->string('detection_source')->nullable();
            $table->string('status')->default('pending');
            $table->text('error_message')->nullable();
            $table->boolean('is_current')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->index(['plan_sheet_id', 'is_current']);
            $table->index(['plan_set_id', 'page_number']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_sheet_revisions');
    }
};
