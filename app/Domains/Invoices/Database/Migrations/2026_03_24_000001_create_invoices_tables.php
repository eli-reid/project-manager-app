<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('invoices')) {
            Schema::create('invoices', function (Blueprint $table): void {
                $table->ulid('id')->primary();
                $table->foreignUlid('project_id')->constrained('projects')->cascadeOnDelete();
                $table->string('vendor_name');
                $table->string('invoice_number')->nullable();
                $table->date('invoice_date');
                $table->date('due_date')->nullable();
                $table->date('payment_date')->nullable();
                $table->decimal('subtotal', 10, 2)->default(0);
                $table->decimal('tax_amount', 10, 2)->default(0);
                $table->decimal('total_amount', 10, 2)->default(0);
                $table->string('status')->default('pending');
                $table->text('notes')->nullable();
                $table->foreignUlid('created_by')->constrained('users')->restrictOnDelete();
                $table->foreignUlid('verified_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('verified_at')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['project_id', 'status']);
                $table->index('invoice_date');
                $table->index('vendor_name');
            });
        }

        if (! Schema::hasTable('invoice_line_items')) {
            Schema::create('invoice_line_items', function (Blueprint $table): void {
                $table->ulid('id')->primary();
                $table->foreignUlid('invoice_id')->constrained('invoices')->cascadeOnDelete();
                $table->string('description');
                $table->decimal('quantity', 8, 2)->default(1);
                $table->decimal('unit_price', 10, 2)->default(0);
                $table->decimal('total', 10, 2)->default(0);
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_line_items');
        Schema::dropIfExists('invoices');
    }
};
