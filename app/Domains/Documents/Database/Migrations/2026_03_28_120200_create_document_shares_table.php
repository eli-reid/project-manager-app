<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('document_shares')) {
            Schema::create('document_shares', function (Blueprint $table): void {
                $table->ulid('id')->primary();
                $table->foreignUlid('document_id')->constrained('documents')->cascadeOnDelete();
                $table->foreignUlid('created_by_id')->constrained('users')->cascadeOnDelete();
                $table->string('share_token', 64)->unique()->index();
                $table->string('share_password', 64)->nullable();
                $table->timestamp('expires_at')->nullable()->index();
                $table->unsignedInteger('max_downloads')->nullable();
                $table->unsignedInteger('download_count')->default(0);
                $table->boolean('is_active')->default(true)->index();
                $table->text('access_notes')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['document_id', 'is_active']);
                $table->index('created_by_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('document_shares');
    }
};
