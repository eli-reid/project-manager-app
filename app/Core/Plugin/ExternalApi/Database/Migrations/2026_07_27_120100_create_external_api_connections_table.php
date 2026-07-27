<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('external_api_connections')) {
            return;
        }

        Schema::create('external_api_connections', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('driver', 60);
            $table->string('base_url')->nullable();
            $table->string('auth_type', 60)->nullable();
            $table->string('status', 40);
            $table->string('trust_level', 40);
            $table->string('execution_mode', 40);
            $table->json('allowed_scopes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('last_verified_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_api_connections');
    }
};
