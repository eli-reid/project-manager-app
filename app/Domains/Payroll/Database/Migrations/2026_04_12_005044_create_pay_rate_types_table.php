<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('pay_rate_types')) {
            Schema::create('pay_rate_types', function (Blueprint $table): void {
                $table->ulid('id')->primary();
                $table->string('key')->unique();
                $table->string('name');
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->boolean('is_system')->default(false);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
                $table->softDeletes();

                $table->index(['is_active', 'sort_order']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('pay_rate_types')) {
            Schema::dropIfExists('pay_rate_types');
        }
    }
};
