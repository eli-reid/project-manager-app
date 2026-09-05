<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('project_payment_receipts')) {
            return;
        }

        Schema::create('project_payment_receipts', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('project_id')->constrained('projects')->cascadeOnDelete();
            $table->date('received_on');
            $table->decimal('amount', 10, 2);
            $table->string('received_from')->nullable();
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->foreignUlid('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['project_id', 'received_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_payment_receipts');
    }
};
