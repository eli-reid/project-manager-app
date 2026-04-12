<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('payroll_employee_profiles')) {
            Schema::create('payroll_employee_profiles', function (Blueprint $table): void {
                $table->ulid('id')->primary();
                $table->foreignUlid('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('employee_number', 20)->unique();
                $table->text('ssn_encrypted');
                $table->date('date_of_birth');
                $table->date('hire_date');
                $table->date('termination_date')->nullable();
                $table->string('status')->default('active');
                $table->string('pay_type')->default('hourly');
                $table->string('department')->nullable();
                $table->string('job_classification');
                $table->string('union_code', 20)->nullable();
                $table->boolean('direct_deposit_active')->default(false);
                $table->timestamps();
                $table->softDeletes();

                $table->unique('user_id');
                $table->index(['status', 'pay_type']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('payroll_employee_profiles')) {
            Schema::dropIfExists('payroll_employee_profiles');
        }
    }
};
