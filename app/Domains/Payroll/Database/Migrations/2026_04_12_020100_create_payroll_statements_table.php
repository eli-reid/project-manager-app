<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('payroll_statements')) {
            Schema::create('payroll_statements', function (Blueprint $table): void {
                $table->ulid('id')->primary();
                $table->foreignUlid('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignUlid('payroll_employee_profile_id')->constrained('payroll_employee_profiles')->cascadeOnDelete();
                $table->foreignUlid('pay_run_id')->nullable()->constrained('pay_runs')->nullOnDelete();
                $table->decimal('total_regular_hours', 7, 2)->default(0);
                $table->decimal('total_ot_hours', 7, 2)->default(0);
                $table->decimal('total_dt_hours', 7, 2)->default(0);
                $table->decimal('gross_pay', 12, 2)->default(0);
                $table->decimal('federal_tax', 10, 2)->default(0);
                $table->decimal('state_tax', 10, 2)->default(0);
                $table->decimal('local_tax', 10, 2)->default(0);
                $table->decimal('social_security', 10, 2)->default(0);
                $table->decimal('medicare', 10, 2)->default(0);
                $table->decimal('other_deductions', 10, 2)->default(0);
                $table->decimal('net_pay', 12, 2)->default(0);
                $table->decimal('ytd_gross', 14, 2)->default(0);
                $table->decimal('ytd_federal_tax', 12, 2)->default(0);
                $table->decimal('ytd_net', 14, 2)->default(0);
                $table->timestamps();

                $table->index(['user_id', 'payroll_employee_profile_id']);
                $table->index(['pay_run_id', 'user_id']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('payroll_statements')) {
            Schema::dropIfExists('payroll_statements');
        }
    }
};
