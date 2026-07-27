<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('installed_plugins')) {
            return;
        }

        Schema::create('installed_plugins', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('provider_class')->unique();
            $table->string('package_name')->nullable();
            $table->string('version')->nullable();
            $table->string('source_type', 40);
            $table->string('status', 40);
            $table->string('security_status', 40);
            $table->string('manifest_checksum')->nullable();
            $table->string('signature_fingerprint')->nullable();
            $table->json('capabilities')->nullable();
            $table->json('required_permissions')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('installed_at')->nullable();
            $table->timestamp('last_reviewed_at')->nullable();
            $table->foreignUlid('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('installed_plugins');
    }
};
