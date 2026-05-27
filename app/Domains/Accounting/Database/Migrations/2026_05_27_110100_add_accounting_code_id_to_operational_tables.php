<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('projects') && ! Schema::hasColumn('projects', 'accounting_code_id')) {
            Schema::table('projects', function (Blueprint $table): void {
                $table->foreignUlid('accounting_code_id')->nullable()->after('accounting_code')->constrained('accounting_codes')->nullOnDelete();
                $table->index('accounting_code_id');
            });
        }

        if (Schema::hasTable('invoices') && ! Schema::hasColumn('invoices', 'accounting_code_id')) {
            Schema::table('invoices', function (Blueprint $table): void {
                $table->foreignUlid('accounting_code_id')->nullable()->after('project_id')->constrained('accounting_codes')->nullOnDelete();
                $table->index(['accounting_code_id', 'status']);
            });
        }

        if (Schema::hasTable('stock_orders') && ! Schema::hasColumn('stock_orders', 'accounting_code_id')) {
            Schema::table('stock_orders', function (Blueprint $table): void {
                $table->foreignUlid('accounting_code_id')->nullable()->after('project_id')->constrained('accounting_codes')->nullOnDelete();
                $table->index(['accounting_code_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('stock_orders') && Schema::hasColumn('stock_orders', 'accounting_code_id')) {
            Schema::table('stock_orders', function (Blueprint $table): void {
                $table->dropIndex(['accounting_code_id', 'status']);
                $table->dropConstrainedForeignId('accounting_code_id');
            });
        }

        if (Schema::hasTable('invoices') && Schema::hasColumn('invoices', 'accounting_code_id')) {
            Schema::table('invoices', function (Blueprint $table): void {
                $table->dropIndex(['accounting_code_id', 'status']);
                $table->dropConstrainedForeignId('accounting_code_id');
            });
        }

        if (Schema::hasTable('projects') && Schema::hasColumn('projects', 'accounting_code_id')) {
            Schema::table('projects', function (Blueprint $table): void {
                $table->dropIndex(['accounting_code_id']);
                $table->dropConstrainedForeignId('accounting_code_id');
            });
        }
    }
};
