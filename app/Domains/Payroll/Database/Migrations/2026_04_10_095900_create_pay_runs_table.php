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
        Schema::create('pay_runs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('payroll_period_id')->constrained('payroll_periods');
            $table->enum('status', ['draft', 'provisional', 'approved', 'final'])->default('draft');
            $table->decimal('total_gross', 12, 2)->default(0);
            $table->decimal('total_deductions', 12, 2)->default(0);
            $table->decimal('total_net', 12, 2)->default(0);
            $table->integer('records_count')->default(0);
            $table->dateTime('approved_at')->nullable();
            $table->foreignUlid('approved_by')->nullable()->constrained('users');
            $table->foreignUlid('created_by')->nullable()->constrained('users');
            $table->foreignUlid('updated_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('payroll_period_id');
            $table->index('status');
            $table->foreign('payroll_period_id')->references('id')->on('payroll_periods')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pay_runs');
    }
};
