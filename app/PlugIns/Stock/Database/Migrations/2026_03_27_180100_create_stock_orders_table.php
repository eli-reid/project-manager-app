<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('stock_orders')) {
            Schema::create('stock_orders', function (Blueprint $table): void {
                $table->ulid('id')->primary();
                $table->foreignUlid('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignUlid('project_id')->nullable()->constrained('projects')->nullOnDelete();
                $table->string('po_number')->nullable();
                $table->string('status')->default('pending');
                $table->string('urgency')->default('medium');
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['user_id', 'status']);
                $table->index(['project_id', 'status']);
                $table->index('urgency');
                $table->index('po_number');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_orders');
    }
};
