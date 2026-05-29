<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('accounting_journal_entries')) {
            Schema::table('accounting_journal_entries', function (Blueprint $table): void {
                if (! Schema::hasColumn('accounting_journal_entries', 'reversal_of_id')) {
                    $table->foreignUlid('reversal_of_id')->nullable()->after('source_id')->constrained('accounting_journal_entries')->nullOnDelete();
                    $table->unique('reversal_of_id');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('accounting_journal_entries') && Schema::hasColumn('accounting_journal_entries', 'reversal_of_id')) {
            Schema::table('accounting_journal_entries', function (Blueprint $table): void {
                $table->dropUnique(['reversal_of_id']);
                $table->dropConstrainedForeignId('reversal_of_id');
            });
        }
    }
};