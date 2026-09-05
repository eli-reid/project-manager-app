<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rfi_email_deliveries', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('rfi_id')->constrained('rfis')->cascadeOnDelete();
            $table->foreignUlid('sent_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('recipients');
            $table->string('subject');
            $table->text('cover_message')->nullable();
            $table->timestamp('sent_at');
            $table->timestamps();

            $table->index(['rfi_id', 'sent_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rfi_email_deliveries');
    }
};
