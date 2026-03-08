<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('scheduled_tasks')) {
            Schema::create('scheduled_tasks', function (Blueprint $table): void {
                $table->ulid('id')->primary();
                $table->string('name');
                $table->string('feature_type');
                $table->text('description')->nullable();

                $table->enum('schedule_type', ['daily', 'weekly', 'monthly', 'yearly', 'specific_date']);
                $table->time('time');
                $table->string('timezone', 50)->default('America/New_York');
                $table->json('days_of_week')->nullable();
                $table->unsignedTinyInteger('day_of_month')->nullable();
                $table->unsignedTinyInteger('month')->nullable();
                $table->date('specific_date')->nullable();

                $table->enum('repeat_frequency', ['once', 'daily', 'weekly', 'monthly', 'yearly'])->default('once');
                $table->unsignedInteger('repeat_interval')->default(1);
                $table->date('repeat_until')->nullable();
                $table->unsignedInteger('max_occurrences')->nullable();

                $table->boolean('is_active')->default(true);
                $table->boolean('is_enabled')->default(true);
                $table->timestamp('last_run_at')->nullable();
                $table->timestamp('next_run_at')->nullable();
                $table->unsignedInteger('run_count')->default(0);

                $table->json('task_config')->nullable();

                $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignUlid('updated_by')->nullable()->constrained('users')->nullOnDelete();

                $table->timestamps();

                $table->index(['is_active', 'is_enabled', 'next_run_at']);
                $table->index(['feature_type', 'is_active']);
                $table->index('next_run_at');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('scheduled_tasks')) {
            Schema::dropIfExists('scheduled_tasks');
        }
    }
};
