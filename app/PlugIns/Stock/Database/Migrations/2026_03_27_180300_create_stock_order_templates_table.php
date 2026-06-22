<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('stock_order_templates')) {
            Schema::create('stock_order_templates', function (Blueprint $table): void {
                $table->ulid('id')->primary();
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('urgency')->default('medium');
                $table->text('notes')->nullable();
                $table->json('template_items');
                $table->boolean('is_active')->default(true);
                $table->boolean('is_global')->default(false);
                $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['is_active', 'created_at']);
                $table->index(['is_global', 'is_active']);
                $table->index(['created_by', 'is_active']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_order_templates');
    }
};
