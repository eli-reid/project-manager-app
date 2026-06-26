<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The old pay_rates table (batch 14, now-deleted migration) had a legacy schema
     * (ulid PK, user_id, rate, end_date, notes, created_by, updated_by) that does not
     * match the Payroll domain model. The domain migration (batch 15) was silently
     * skipped because the table already existed. This migration drops the stale table
     * and rebuilds it with the correct schema.
     */
    public function up(): void
    {
        if (Schema::hasTable('pay_rates') && ! Schema::hasColumn('pay_rates', 'pay_rate_type_id')) {
            Schema::drop('pay_rates');
        }

        if (! Schema::hasTable('pay_rates')) {
            Schema::create('pay_rates', function (Blueprint $table): void {
                $table->ulid('id')->primary();
                $table->foreignUlid('payroll_employee_profile_id')->constrained('payroll_employee_profiles')->cascadeOnDelete();
                $table->foreignUlid('pay_rate_type_id')->constrained('pay_rate_types');
                $table->foreignUlid('project_id')->nullable()->constrained('projects')->nullOnDelete();
                $table->decimal('rate_amount', 10, 4);
                $table->date('effective_date');
                $table->date('expiration_date')->nullable();
                $table->foreignUlid('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['payroll_employee_profile_id', 'pay_rate_type_id'], 'pay_rates_profile_type_idx');
                $table->index(['project_id', 'effective_date']);
                $table->index(['effective_date', 'expiration_date']);
                $table->unique(
                    ['payroll_employee_profile_id', 'pay_rate_type_id', 'project_id', 'effective_date'],
                    'pay_rates_profile_type_project_effective_unique'
                );
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pay_rates');
    }
};
