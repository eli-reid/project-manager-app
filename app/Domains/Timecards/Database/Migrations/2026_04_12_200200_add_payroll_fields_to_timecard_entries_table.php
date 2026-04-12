<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('timecard_entries', function (Blueprint $table): void {
            $table->foreignUlid('cost_code_id')->nullable()->constrained('cost_codes')->nullOnDelete()->after('project_id');
            $table->decimal('regular_hours', 8, 2)->nullable()->after('hours');
            $table->decimal('overtime_hours', 8, 2)->nullable()->after('regular_hours');
            $table->decimal('double_time_hours', 8, 2)->nullable()->after('overtime_hours');
            $table->string('work_classification')->nullable()->after('double_time_hours');
            $table->decimal('prevailing_base_rate', 10, 4)->nullable()->after('work_classification');
            $table->decimal('prevailing_fringe_rate', 10, 4)->nullable()->after('prevailing_base_rate');
            $table->string('fringe_payment_method')->nullable()->after('prevailing_fringe_rate');
        });
    }

    public function down(): void
    {
        Schema::table('timecard_entries', function (Blueprint $table): void {
            $table->dropForeign(['cost_code_id']);
            $table->dropColumn([
                'cost_code_id',
                'regular_hours',
                'overtime_hours',
                'double_time_hours',
                'work_classification',
                'prevailing_base_rate',
                'prevailing_fringe_rate',
                'fringe_payment_method',
            ]);
        });
    }
};
