<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('plan_sheets')) {
            return;
        }

        Schema::create('plan_sheets', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('sheet_number')->nullable();
            $table->string('title')->nullable();
            $table->string('discipline')->nullable();
            $table->integer('sort_index')->default(0);
            $table->ulid('current_revision_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['project_id', 'sheet_number']);
            $table->index(['project_id', 'discipline']);
            $table->index(['project_id', 'sort_index']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_sheets');
    }
};
