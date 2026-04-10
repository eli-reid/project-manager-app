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
        Schema::create('payroll_corrections', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('payroll_record_id')->constrained('payroll_records');
            $table->enum('type', ['adjustment', 'refund', 'reversal'])->default('adjustment');
            $table->enum('status', ['pending', 'approved', 'rejected', 'applied'])->default('pending');
            $table->decimal('amount', 12, 2);
            $table->text('description');
            $table->string('reason')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->foreignUlid('approved_by')->nullable()->constrained('users');
            $table->dateTime('applied_at')->nullable();
            $table->foreignUlid('created_by')->nullable()->constrained('users');
            $table->foreignUlid('updated_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('payroll_record_id');
            $table->index('status');
            $table->index('type');
            $table->foreign('payroll_record_id')->references('id')->on('payroll_records')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_corrections');
    }
};
