<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('payroll_employee_profiles')) {
            Schema::table('payroll_employee_profiles', function (Blueprint $table): void {
                if (! Schema::hasColumn('payroll_employee_profiles', 'sick_hours_allowance')) {
                    $table->decimal('sick_hours_allowance', 8, 2)->default(0);
                }

                if (! Schema::hasColumn('payroll_employee_profiles', 'vacation_hours_allowance')) {
                    $table->decimal('vacation_hours_allowance', 8, 2)->default(0);
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('payroll_employee_profiles')) {
            Schema::table('payroll_employee_profiles', function (Blueprint $table): void {
                if (Schema::hasColumn('payroll_employee_profiles', 'sick_hours_allowance')) {
                    $table->dropColumn('sick_hours_allowance');
                }

                if (Schema::hasColumn('payroll_employee_profiles', 'vacation_hours_allowance')) {
                    $table->dropColumn('vacation_hours_allowance');
                }
            });
        }
    }
};
