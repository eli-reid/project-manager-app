<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('timecard_entries')) {
            Schema::create('timecard_entries', function (Blueprint $table): void {
                $table->ulid('id')->primary();
                $table->foreignUlid('timecard_id')->constrained('timecards')->cascadeOnDelete();
                $table->foreignUlid('user_id')->constrained('users');
                $table->foreignUlid('project_id')->nullable()->constrained('projects')->nullOnDelete();
                $table->string('custom_project_name')->nullable();
                $table->date('date');
                $table->time('start_time')->nullable();
                $table->decimal('hours', 8, 2)->default(0);
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['timecard_id', 'date']);
                $table->index(['user_id', 'date']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('timecard_entries')) {
            Schema::dropIfExists('timecard_entries');
        }
    }
};
