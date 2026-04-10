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
        Schema::create('burden_rates', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->nullable()->constrained('users');
            $table->enum('scope', ['global', 'user'])->default('global');
            $table->string('component_name'); // e.g., 'federal_tax', 'state_tax', 'medicare'
            $table->decimal('percentage', 10, 4)->nullable(); // For percentage-based calculations
            $table->decimal('amount', 10, 2)->nullable(); // For fixed amount calculations
            $table->date('effective_date');
            $table->date('end_date')->nullable();
            $table->text('description')->nullable();
            $table->foreignUlid('created_by')->nullable()->constrained('users');
            $table->foreignUlid('updated_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('scope');
            $table->index('user_id');
            $table->index('effective_date');
            $table->index(['user_id', 'effective_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('burden_rates');
    }
};
