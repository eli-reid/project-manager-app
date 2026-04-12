<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('cost_codes')) {
            Schema::create('cost_codes', function (Blueprint $table): void {
                $table->ulid('id')->primary();
                $table->foreignUlid('project_id')->constrained('projects')->cascadeOnDelete();
                $table->string('code', 20);
                $table->string('description', 100);
                $table->decimal('budget_hours', 10, 2)->nullable();
                $table->decimal('budget_cost', 12, 2)->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();

                $table->index(['project_id', 'is_active']);
                $table->unique(['project_id', 'code']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('cost_codes')) {
            Schema::dropIfExists('cost_codes');
        }
    }
};
