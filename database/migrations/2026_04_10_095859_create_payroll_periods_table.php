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
        Schema::create('payroll_periods', function (Blueprint $table) {
            $table->ulid()->primary();
            $table->date('period_start_date');
            $table->date('period_end_date');
            $table->enum('status', ['open', 'locked', 'finalized'])->default('open');
            $table->dateTime('finalized_at')->nullable();
            $table->foreignUlid('finalized_by')->nullable()->constrained('users');
            $table->text('notes')->nullable();
            $table->foreignUlid('created_by')->nullable()->constrained('users');
            $table->foreignUlid('updated_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('period_start_date');
            $table->index('status');
            $table->unique(['period_start_date', 'period_end_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_periods');
    }
};
