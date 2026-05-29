<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('accounting_journal_lines')) {
            Schema::create('accounting_journal_lines', function (Blueprint $table): void {
                $table->ulid('id')->primary();
                $table->foreignUlid('accounting_journal_entry_id')->constrained('accounting_journal_entries')->cascadeOnDelete();
                $table->foreignUlid('accounting_code_id')->constrained('accounting_codes')->restrictOnDelete();
                $table->unsignedSmallInteger('line_number');
                $table->string('description')->nullable();
                $table->decimal('debit_amount', 12, 2)->default(0);
                $table->decimal('credit_amount', 12, 2)->default(0);
                $table->timestamps();

                $table->index(['accounting_journal_entry_id', 'line_number']);
                $table->index(['accounting_code_id', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_journal_lines');
    }
};
