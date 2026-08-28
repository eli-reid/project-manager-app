<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('user_addresses')) {
            Schema::create('user_addresses', function (Blueprint $table): void {
                $table->id();
                $table->foreignUlid('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignUlid('address_id')->constrained('addresses')->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['user_id', 'address_id']);
                $table->index('address_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('user_addresses');
    }
};
