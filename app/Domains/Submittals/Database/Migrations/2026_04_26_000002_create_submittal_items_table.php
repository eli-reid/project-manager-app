<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('submittal_items')) {
            Schema::create('submittal_items', function (Blueprint $table) {
                $table->ulid('id')->primary();
                $table->ulid('submittal_id');
                $table->string('description');
                $table->string('manufacturer')->nullable();
                $table->string('model')->nullable();
                $table->string('part_number')->nullable();
                $table->decimal('quantity', 10, 2)->nullable();
                $table->string('unit')->nullable();
                $table->string('status')->default('pending');
                $table->text('comments')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['submittal_id', 'status']);

                $table->foreign('submittal_id')->references('id')->on('submittals');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('submittal_items');
    }
};
