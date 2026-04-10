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
        Schema::create('payroll_records', function (Blueprint $table) {
            $table->ulid()->primary();
            $table->foreignUlid('pay_run_id')->constrained('pay_runs');
            $table->foreignUlid('user_id')->constrained('users');
            $table->decimal('regular_hours', 8, 2)->default(0);
            $table->decimal('overtime_hours', 8, 2)->default(0);
            $table->decimal('gross_amount', 12, 2)->default(0);
            $table->decimal('federal_tax', 10, 2)->default(0);
            $table->decimal('state_tax', 10, 2)->default(0);
            $table->decimal('local_tax', 10, 2)->default(0);
            $table->decimal('social_security', 10, 2)->default(0);
            $table->decimal('medicare', 10, 2)->default(0);
            $table->decimal('total_deductions', 12, 2)->default(0);
            $table->decimal('net_amount', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->foreignUlid('created_by')->nullable()->constrained('users');
            $table->foreignUlid('updated_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('pay_run_id');
            $table->index('user_id');
            $table->unique(['pay_run_id', 'user_id']);
            $table->foreign('pay_run_id')->references('id')->on('pay_runs')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_records');
    }
};
