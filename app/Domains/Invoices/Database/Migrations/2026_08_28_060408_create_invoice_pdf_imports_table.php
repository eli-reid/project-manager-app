<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('invoice_pdf_imports')) {
            Schema::create('invoice_pdf_imports', function (Blueprint $table): void {
                $table->ulid('id')->primary();
                $table->foreignUlid('project_id')->constrained('projects')->cascadeOnDelete();
                $table->foreignUlid('created_by')->constrained('users')->cascadeOnDelete();
                $table->string('file_path');
                $table->string('status')->default('pending');
                $table->json('parsed_data')->nullable();
                $table->text('error_message')->nullable();
                $table->timestamps();

                $table->index(['project_id', 'status']);
                $table->index('created_by');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_pdf_imports');
    }
};
