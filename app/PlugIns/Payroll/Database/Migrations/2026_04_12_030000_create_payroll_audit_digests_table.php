<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('payroll_audit_digests')) {
            Schema::create('payroll_audit_digests', function (Blueprint $table): void {
                $table->ulid('id')->primary();
                $table->string('chain_key', 64)->default('payroll');
                $table->foreignUlid('audit_log_id')->nullable()->constrained('audit_logs')->nullOnDelete();
                $table->char('payload_hash', 64);
                $table->char('digest', 64);
                $table->char('previous_digest', 64)->nullable();
                $table->boolean('is_valid')->default(true);
                $table->timestamp('validated_at')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['chain_key', 'created_at']);
                $table->unique(['chain_key', 'audit_log_id']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('payroll_audit_digests')) {
            Schema::dropIfExists('payroll_audit_digests');
        }
    }
};
