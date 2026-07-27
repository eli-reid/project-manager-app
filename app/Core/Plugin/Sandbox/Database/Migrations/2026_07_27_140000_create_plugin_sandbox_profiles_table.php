<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('plugin_sandbox_profiles')) {
            return;
        }

        Schema::create('plugin_sandbox_profiles', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('isolation_driver', 60);
            $table->string('status', 40);
            $table->json('applies_to_trust_levels')->nullable();
            $table->json('allowed_host_apis')->nullable();
            $table->json('resource_limits')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('last_verified_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plugin_sandbox_profiles');
    }
};
