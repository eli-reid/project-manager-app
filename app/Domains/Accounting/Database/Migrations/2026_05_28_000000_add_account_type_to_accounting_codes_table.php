<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('accounting_codes') && ! Schema::hasColumn('accounting_codes', 'account_type')) {
            Schema::table('accounting_codes', function (Blueprint $table): void {
                $table->string('account_type', 20)->default('other')->after('name');
                $table->index(['account_type', 'is_active']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('accounting_codes') && Schema::hasColumn('accounting_codes', 'account_type')) {
            Schema::table('accounting_codes', function (Blueprint $table): void {
                $table->dropIndex(['account_type', 'is_active']);
                $table->dropColumn('account_type');
            });
        }
    }
};
