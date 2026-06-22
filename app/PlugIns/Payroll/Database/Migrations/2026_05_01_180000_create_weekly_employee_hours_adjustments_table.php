<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('weekly_employee_hours_adjustments')) {
            Schema::create('weekly_employee_hours_adjustments', function (Blueprint $table): void {
                $table->ulid('id')->primary();
                $table->date('week_start');
                $table->foreignUlid('user_id')->constrained('users')->cascadeOnDelete();
                $table->decimal('source_hours', 8, 2);
                $table->decimal('adjusted_hours', 8, 2);
                $table->text('reason');
                $table->foreignUlid('edited_by_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('edited_at')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['week_start', 'user_id']);
                $table->unique(['week_start', 'user_id']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('weekly_employee_hours_adjustments')) {
            Schema::dropIfExists('weekly_employee_hours_adjustments');
        }
    }
};
