<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('document_project_owners')) {
            Schema::create('document_project_owners', function (Blueprint $table): void {
                $table->id();
                $table->foreignUlid('document_id')->constrained('documents')->cascadeOnDelete();
                $table->foreignUlid('project_id')->constrained('projects')->cascadeOnDelete();
                $table->timestamps();

                $table->unique('document_id');
                $table->unique(['document_id', 'project_id']);
                $table->index('project_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('document_project_owners');
    }
};
