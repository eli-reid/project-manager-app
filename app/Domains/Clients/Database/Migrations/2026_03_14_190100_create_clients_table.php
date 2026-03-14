<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('clients')) {
            Schema::create('clients', function (Blueprint $table): void {
                $table->ulid('id')->primary();
                $table->string('company_name');
                $table->string('contact_name')->nullable();
                $table->string('email')->nullable();
                $table->string('phone')->nullable();
                $table->string('mobile')->nullable();
                $table->text('notes')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();

                $table->index(['company_name', 'is_active']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('clients')) {
            Schema::dropIfExists('clients');
        }
    }
};
