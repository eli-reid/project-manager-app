<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('accounting_codes')) {
            Schema::create('accounting_codes', function (Blueprint $table): void {
                $table->ulid('id')->primary();
                $table->string('code')->unique();
                $table->string('name');
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();

                $table->index(['is_active', 'code']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('accounting_codes')) {
            Schema::dropIfExists('accounting_codes');
        }
    }
};
