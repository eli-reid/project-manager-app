<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('plan_annotations')) {
            return;
        }

        Schema::create('plan_annotations', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('plan_sheet_id')->constrained('plan_sheets')->cascadeOnDelete();
            $table->foreignUlid('plan_sheet_revision_id')->nullable()->constrained('plan_sheet_revisions')->nullOnDelete();
            $table->foreignUlid('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type');
            $table->json('geometry');
            $table->json('style');
            $table->text('content')->nullable();
            $table->string('status')->default('open');
            $table->ulid('linked_task_id')->nullable();
            $table->string('visibility')->default('project');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['plan_sheet_id', 'status']);
            $table->index(['plan_sheet_id', 'plan_sheet_revision_id']);
            $table->index('author_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_annotations');
    }
};
