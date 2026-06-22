<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('stock_order_items')) {
            Schema::create('stock_order_items', function (Blueprint $table): void {
                $table->ulid('id')->primary();
                $table->foreignUlid('stock_order_id')->constrained('stock_orders')->cascadeOnDelete();
                $table->unsignedInteger('quantity')->default(1);
                $table->string('item_name');
                $table->string('status')->default('pending');
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['stock_order_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_order_items');
    }
};
