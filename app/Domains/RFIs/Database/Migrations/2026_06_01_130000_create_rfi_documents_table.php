<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('rfi_documents')) {
            return;
        }

        Schema::create('rfi_documents', function (Blueprint $table): void {
            $table->foreignUlid('rfi_id')->constrained('rfis')->cascadeOnDelete();
            $table->foreignUlid('document_id')->constrained('documents')->cascadeOnDelete();
            $table->string('document_role')->default('reference');
            $table->string('document_status')->default('active');
            $table->string('revision', 40)->nullable();
            $table->string('discipline', 60)->nullable();
            $table->timestamps();

            $table->primary(['rfi_id', 'document_id']);
            $table->index(['rfi_id', 'document_role', 'document_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rfi_documents');
    }
};
