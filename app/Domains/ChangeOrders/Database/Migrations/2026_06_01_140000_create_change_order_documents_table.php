<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('change_order_documents')) {
            return;
        }

        Schema::create('change_order_documents', function (Blueprint $table): void {
            $table->foreignUlid('change_order_id')->constrained('change_orders')->cascadeOnDelete();
            $table->foreignUlid('document_id')->constrained('documents')->cascadeOnDelete();
            $table->string('document_role')->default('reference');
            $table->string('document_status')->default('active');
            $table->string('revision', 40)->nullable();
            $table->string('discipline', 60)->nullable();
            $table->timestamps();

            $table->primary(['change_order_id', 'document_id']);
            $table->index(['change_order_id', 'document_role', 'document_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('change_order_documents');
    }
};
