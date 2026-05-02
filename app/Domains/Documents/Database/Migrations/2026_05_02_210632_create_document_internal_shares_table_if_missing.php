<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('document_internal_shares')) {
            Schema::create('document_internal_shares', function (Blueprint $table): void {
                $table->ulid('id')->primary();
                $table->foreignUlid('document_id')->constrained('documents')->cascadeOnDelete();
                $table->string('grantee_scope', 20);
                $table->char('grantee_id', 26);
                $table->string('permission_level', 20)->default('view');
                $table->foreignUlid('granted_by_id')->constrained('users')->cascadeOnDelete();
                $table->timestamp('expires_at')->nullable()->index();
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['document_id', 'grantee_scope', 'grantee_id'], 'document_internal_shares_unique_grantee');
                $table->index(['grantee_scope', 'grantee_id']);
                $table->index(['document_id', 'permission_level']);
                $table->index('granted_by_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('document_internal_shares');
    }
};
