<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('employee_deductions')) {
            Schema::create('employee_deductions', function (Blueprint $table): void {
                $table->ulid('id')->primary();
                $table->foreignUlid('payroll_employee_profile_id')->constrained('payroll_employee_profiles')->cascadeOnDelete();
                $table->foreignUlid('deduction_id')->constrained('deductions')->cascadeOnDelete();
                $table->decimal('override_amount', 10, 4)->nullable();
                $table->date('effective_date');
                $table->date('end_date')->nullable();
                $table->string('status')->default('active');
                $table->timestamps();
                $table->softDeletes();

                $table->index(['payroll_employee_profile_id', 'status']);
                $table->index(['effective_date', 'end_date']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('employee_deductions')) {
            Schema::dropIfExists('employee_deductions');
        }
    }
};
