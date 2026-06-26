<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('pay_runs')) {
            return;
        }

        if (Schema::hasTable('pay_runs_rebuild_tmp')) {
            Schema::drop('pay_runs_rebuild_tmp');
        }

        $hasId = Schema::hasColumn('pay_runs', 'id');
        $hasPeriodStart = Schema::hasColumn('pay_runs', 'pay_period_start');

        // Already on current schema.
        if ($hasId && $hasPeriodStart) {
            return;
        }

        $columns = Schema::getColumnListing('pay_runs');

        $sourceIdColumn = in_array('id', $columns, true) ? 'id' : (in_array('ulid', $columns, true) ? 'ulid' : null);
        $sourceEmployeeCountColumn = in_array('employee_count', $columns, true)
            ? 'employee_count'
            : (in_array('records_count', $columns, true) ? 'records_count' : null);
        $sourceTotalTaxesColumn = in_array('total_taxes', $columns, true)
            ? 'total_taxes'
            : (in_array('total_deductions', $columns, true) ? 'total_deductions' : null);

        $approvedAtExpression = in_array('approved_at', $columns, true) ? 'pr.approved_at' : 'NULL';
        $finalizedAtExpression = in_array('finalized_at', $columns, true) ? 'pr.finalized_at' : 'NULL';

        if ($sourceIdColumn === null) {
            return;
        }

        Schema::disableForeignKeyConstraints();

        Schema::create('pay_runs_rebuild_tmp', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->date('pay_period_start');
            $table->date('pay_period_end');
            $table->date('pay_date');
            $table->string('status')->default('draft');
            $table->decimal('total_gross', 14, 2)->default(0);
            $table->decimal('total_net', 14, 2)->default(0);
            $table->decimal('total_taxes', 14, 2)->default(0);
            $table->unsignedInteger('employee_count')->default(0);
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('finalized_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['pay_period_start', 'pay_period_end']);
            $table->index(['status', 'pay_date']);
        });

        $hasLegacyPayrollPeriodId = in_array('payroll_period_id', $columns, true)
            && Schema::hasTable('payroll_periods')
            && Schema::hasColumn('payroll_periods', 'ulid')
            && Schema::hasColumn('payroll_periods', 'period_start_date')
            && Schema::hasColumn('payroll_periods', 'period_end_date');

        if ($hasLegacyPayrollPeriodId) {
            DB::statement(
                "INSERT INTO pay_runs_rebuild_tmp (
                    id,
                    pay_period_start,
                    pay_period_end,
                    pay_date,
                    status,
                    total_gross,
                    total_net,
                    total_taxes,
                    employee_count,
                    created_by,
                    approved_by,
                    finalized_at,
                    created_at,
                    updated_at,
                    deleted_at
                )
                SELECT
                    pr.{$sourceIdColumn} as id,
                    COALESCE(pp.period_start_date, date(pr.created_at), date('now')) as pay_period_start,
                    COALESCE(pp.period_end_date, date(pr.created_at), date('now')) as pay_period_end,
                    COALESCE(date({$approvedAtExpression}), date(pr.created_at), date('now')) as pay_date,
                    COALESCE(pr.status, 'draft') as status,
                    COALESCE(pr.total_gross, 0) as total_gross,
                    COALESCE(pr.total_net, 0) as total_net,
                    COALESCE(pr.{$sourceTotalTaxesColumn}, 0) as total_taxes,
                    COALESCE(pr.{$sourceEmployeeCountColumn}, 0) as employee_count,
                    pr.created_by,
                    pr.approved_by,
                    COALESCE({$finalizedAtExpression}, {$approvedAtExpression}) as finalized_at,
                    pr.created_at,
                    pr.updated_at,
                    pr.deleted_at
                FROM pay_runs pr
                LEFT JOIN payroll_periods pp ON pp.ulid = pr.payroll_period_id"
            );
        } else {
            DB::statement(
                "INSERT INTO pay_runs_rebuild_tmp (
                    id,
                    pay_period_start,
                    pay_period_end,
                    pay_date,
                    status,
                    total_gross,
                    total_net,
                    total_taxes,
                    employee_count,
                    created_by,
                    approved_by,
                    finalized_at,
                    created_at,
                    updated_at,
                    deleted_at
                )
                SELECT
                    {$sourceIdColumn} as id,
                    COALESCE(date(created_at), date('now')) as pay_period_start,
                    COALESCE(date(created_at), date('now')) as pay_period_end,
                    COALESCE(date({$approvedAtExpression}), date(created_at), date('now')) as pay_date,
                    COALESCE(status, 'draft') as status,
                    COALESCE(total_gross, 0) as total_gross,
                    COALESCE(total_net, 0) as total_net,
                    COALESCE({$sourceTotalTaxesColumn}, 0) as total_taxes,
                    COALESCE({$sourceEmployeeCountColumn}, 0) as employee_count,
                    created_by,
                    approved_by,
                    COALESCE({$finalizedAtExpression}, {$approvedAtExpression}) as finalized_at,
                    created_at,
                    updated_at,
                    deleted_at
                FROM pay_runs"
            );
        }

        Schema::drop('pay_runs');
        Schema::rename('pay_runs_rebuild_tmp', 'pay_runs');

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        // Irreversible migration: this is a one-way schema repair for legacy local databases.
    }
};
