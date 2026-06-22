<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('deductions')) {
            Schema::create('deductions', function (Blueprint $table): void {
                $table->ulid('id')->primary();
                $table->string('name', 50);
                $table->string('category', 32);
                $table->string('calculation_method', 32);
                $table->decimal('amount', 10, 4);
                $table->unsignedInteger('priority')->default(0);
                $table->boolean('pre_tax')->default(false);
                $table->decimal('max_annual', 10, 2)->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['category', 'priority']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('deductions')) {
            Schema::dropIfExists('deductions');
        }
    }
};
