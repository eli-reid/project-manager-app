<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('cached_email_accounts')) {
            Schema::create('cached_email_accounts', function (Blueprint $table): void {
                $table->ulid('id')->primary();
                $table->string('email')->unique();
                $table->string('domain')->nullable();
                $table->boolean('suspended')->default(false);
                $table->unsignedInteger('quota')->default(0);
                $table->unsignedInteger('usage')->default(0);
                $table->decimal('usage_percentage', 5, 2)->default(0);
                $table->json('raw_data')->nullable();
                $table->foreignUlid('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('last_synced_at')->nullable();
                $table->boolean('sync_failed')->default(false);
                $table->text('sync_error')->nullable();
                $table->timestamps();

                $table->index(['domain', 'suspended']);
                $table->index('usage_percentage');
                $table->index('sync_failed');
                $table->index('last_synced_at');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('cached_email_accounts')) {
            Schema::dropIfExists('cached_email_accounts');
        }
    }
};
