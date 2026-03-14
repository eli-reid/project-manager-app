<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('addresses')) {
            Schema::create('addresses', function (Blueprint $table): void {
                $table->ulid('id')->primary();
                $table->string('address1');
                $table->string('address2')->nullable();
                $table->string('city')->nullable();
                $table->string('state')->nullable();
                $table->string('zip')->nullable();
                $table->string('country')->default('US');
                $table->foreignUlid('client_id')->nullable()->constrained('clients')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['client_id', 'city', 'state']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('addresses')) {
            Schema::dropIfExists('addresses');
        }
    }
};
