<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('accounting_codes')) {
            Schema::table('accounting_codes', function (Blueprint $table): void {
                if (! Schema::hasColumn('accounting_codes', 'parent_id')) {
                    $table->foreignUlid('parent_id')->nullable()->after('account_type')->constrained('accounting_codes')->nullOnDelete();
                    $table->index('parent_id');
                }

                if (! Schema::hasColumn('accounting_codes', 'normal_balance')) {
                    $table->string('normal_balance', 20)->default('debit')->after('parent_id');
                    $table->index(['account_type', 'normal_balance']);
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('accounting_codes')) {
            Schema::table('accounting_codes', function (Blueprint $table): void {
                if (Schema::hasColumn('accounting_codes', 'normal_balance')) {
                    $table->dropIndex(['account_type', 'normal_balance']);
                    $table->dropColumn('normal_balance');
                }

                if (Schema::hasColumn('accounting_codes', 'parent_id')) {
                    $table->dropIndex(['parent_id']);
                    $table->dropConstrainedForeignId('parent_id');
                }
            });
        }
    }
};
