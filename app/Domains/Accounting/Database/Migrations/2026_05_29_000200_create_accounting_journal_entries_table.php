<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('accounting_journal_entries')) {
            Schema::create('accounting_journal_entries', function (Blueprint $table): void {
                $table->ulid('id')->primary();
                $table->string('entry_number')->unique();
                $table->string('description');
                $table->string('source_type')->nullable();
                $table->string('source_id')->nullable();
                $table->timestamp('posted_at');
                $table->timestamps();

                $table->index('posted_at');
                $table->index(['source_type', 'source_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_journal_entries');
    }
};
