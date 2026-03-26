<?php

use App\Domains\Dailies\Models\DailyReport;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('daily_reports')) {
            Schema::create('daily_reports', function (Blueprint $table): void {
                $table->ulid('id')->primary();
                $table->foreignUlid('project_id')->nullable()->constrained('projects')->nullOnDelete();
                $table->string('custom_project_name')->nullable();
                $table->foreignUlid('user_id')->constrained('users');
                $table->foreignUlid('submitted_by_id')->nullable()->constrained('users')->nullOnDelete();
                $table->date('report_date');
                $table->string('status')->default(DailyReport::STATUS_DRAFT);
                $table->json('work_performed')->nullable();
                $table->json('materials_used')->nullable();
                $table->json('equipment_used')->nullable();
                $table->json('safety_issues')->nullable();
                $table->json('delays')->nullable();
                $table->json('visitors')->nullable();
                $table->json('onsite_employees')->nullable();
                $table->string('weather_condition')->nullable();
                $table->decimal('temperature', 8, 2)->nullable();
                $table->string('temperature_unit', 5)->default('F');
                $table->decimal('total_regular_hours', 8, 2)->default(0);
                $table->decimal('total_overtime_hours', 8, 2)->default(0);
                $table->decimal('total_hours', 8, 2)->default(0);
                $table->text('additional_notes')->nullable();
                $table->text('rejection_reason')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['status', 'report_date']);
                $table->index(['user_id', 'report_date']);
                $table->index(['project_id', 'report_date']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('daily_reports')) {
            Schema::dropIfExists('daily_reports');
        }
    }
};
