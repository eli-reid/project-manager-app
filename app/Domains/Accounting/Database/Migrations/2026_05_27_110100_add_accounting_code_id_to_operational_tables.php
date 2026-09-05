<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

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

        $this->backfillAccountingCodeLinks();
    }

    private function backfillAccountingCodeLinks(): void
    {
        if (! Schema::hasTable('accounting_codes') || ! Schema::hasTable('projects')) {
            return;
        }

        $legacyCodes = DB::table('projects')
            ->whereNotNull('accounting_code')
            ->where('accounting_code', '!=', '')
            ->whereNull('accounting_code_id')
            ->distinct()
            ->pluck('accounting_code')
            ->filter(fn ($code): bool => is_string($code) && trim($code) !== '')
            ->values();

        if ($legacyCodes->isEmpty()) {
            return;
        }

        $now = now();

        foreach ($legacyCodes as $code) {
            DB::table('accounting_codes')->insertOrIgnore([
                'id' => (string) Str::ulid(),
                'code' => $code,
                'name' => $code,
                'description' => 'Migrated from existing project accounting code.',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $codeMap = DB::table('accounting_codes')
            ->whereIn('code', $legacyCodes->all())
            ->pluck('id', 'code');

        foreach ($codeMap as $code => $accountingCodeId) {
            DB::table('projects')
                ->where('accounting_code', $code)
                ->whereNull('accounting_code_id')
                ->update(['accounting_code_id' => $accountingCodeId]);
        }

        $projectAccountingMap = DB::table('projects')
            ->whereNotNull('accounting_code_id')
            ->pluck('accounting_code_id', 'id');

        if (Schema::hasTable('invoices') && Schema::hasColumn('invoices', 'accounting_code_id')) {
            foreach ($projectAccountingMap as $projectId => $accountingCodeId) {
                DB::table('invoices')
                    ->where('project_id', $projectId)
                    ->whereNull('accounting_code_id')
                    ->update(['accounting_code_id' => $accountingCodeId]);
            }
        }

        if (Schema::hasTable('stock_orders') && Schema::hasColumn('stock_orders', 'accounting_code_id')) {
            foreach ($projectAccountingMap as $projectId => $accountingCodeId) {
                DB::table('stock_orders')
                    ->where('project_id', $projectId)
                    ->whereNull('accounting_code_id')
                    ->update(['accounting_code_id' => $accountingCodeId]);
            }
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
