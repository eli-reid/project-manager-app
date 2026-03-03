<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('scheduled_tasks', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('feature_type');
            $table->json('payload')->nullable();
            $table->string('schedule_type'); // once, daily, weekly, monthly, cron
            $table->string('cron_expression')->nullable();
            $table->string('day_of_week')->nullable(); // for weekly schedule
            $table->integer('day_of_month')->nullable(); // for monthly schedule
            $table->time('run_time')->nullable(); // for daily/weekly/monthly schedules
            $table->timestamp('run_at')->nullable(); // for once schedule
            $table->string('timezone')->default('UTC');
            $table->timestamp('last_run_at')->nullable();
            $table->timestamp('next_run_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();

            // Indexes for performance
            $table->index(['is_active', 'is_enabled', 'next_run_at']);
            $table->index('feature_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scheduled_tasks');
    }
};
