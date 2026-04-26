<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('submittal_documents')) {
            Schema::create('submittal_documents', function (Blueprint $table): void {
                $table->ulid('submittal_id');
                $table->ulid('document_id');
                $table->timestamps();

                $table->primary(['submittal_id', 'document_id']);
                $table->foreign('submittal_id')->references('id')->on('submittals');
                $table->foreign('document_id')->references('id')->on('documents');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('submittal_documents');
    }
};
