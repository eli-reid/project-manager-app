<?php

namespace App\Domains\Payroll\Services;

use App\Core\Identity\Models\User;
use App\Domains\Payroll\Enums\PayRunStatus;
use App\Domains\Payroll\Models\PayRun;
use App\Domains\Payroll\Notifications\PayRunApprovedNotification;
use App\Domains\Payroll\Notifications\PayRunFinalizedNotification;
use App\Domains\Payroll\Notifications\PayRunVoidedNotification;
use DomainException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PayRunService
{
    public function __construct(
        private readonly PayrollStatementBuilderService $statementBuilderService,
        private readonly PayrollAuditTrailService $auditTrail,
    ) {}

    public function createPreview(
        Carbon|string $payPeriodStart,
        Carbon|string $payPeriodEnd,
        Carbon|string $payDate,
        string $createdBy,
    ): PayRun {
        $periodStart = $payPeriodStart instanceof Carbon ? $payPeriodStart->copy()->startOfDay() : Carbon::parse($payPeriodStart)->startOfDay();
        $periodEnd = $payPeriodEnd instanceof Carbon ? $payPeriodEnd->copy()->endOfDay() : Carbon::parse($payPeriodEnd)->endOfDay();
        $normalizedPayDate = $payDate instanceof Carbon ? $payDate->copy()->startOfDay() : Carbon::parse($payDate)->startOfDay();

        if ($periodEnd->lt($periodStart)) {
            throw new DomainException('Pay period end date must be on or after the start date.');
        }

        return DB::transaction(function () use ($periodStart, $periodEnd, $normalizedPayDate, $createdBy): PayRun {
            $payRun = PayRun::query()->create([
                'pay_period_start' => $periodStart->toDateString(),
                'pay_period_end' => $periodEnd->toDateString(),
                'pay_date' => $normalizedPayDate->toDateString(),
                'status' => PayRunStatus::Preview,
                'created_by' => $createdBy,
                'approved_by' => null,
                'finalized_at' => null,
                'total_gross' => 0,
                'total_net' => 0,
                'total_taxes' => 0,
                'employee_count' => 0,
            ]);

            $this->statementBuilderService->buildForRun($payRun);
            $this->recalculateTotals($payRun);

            $this->auditTrail->record('payroll.pay-runs.preview-created', $payRun, [
                'after' => [
                    'status' => $payRun->status->value,
                    'pay_period_start' => $payRun->pay_period_start?->toDateString(),
                    'pay_period_end' => $payRun->pay_period_end?->toDateString(),
                    'pay_date' => $payRun->pay_date?->toDateString(),
                ],
            ]);

            return $payRun->fresh(['payrollStatements']) ?? $payRun;
        });
    }

    public function approve(PayRun $payRun, string $approvedBy): PayRun
    {
        return DB::transaction(function () use ($payRun, $approvedBy): PayRun {
            $run = $payRun->fresh(['payrollStatements']) ?? $payRun;

            if ($run->payrollStatements->isEmpty()) {
                throw new DomainException('Cannot approve a pay run with no payroll statements.');
            }

            $this->transition($run, PayRunStatus::Approved);
            $run->approved_by = $approvedBy;
            $run->save();

            $this->auditTrail->record('payroll.pay-runs.approved', $run, [
                'after' => [
                    'status' => $run->status->value,
                    'approved_by' => (string) $run->approved_by,
                ],
            ]);

            // Dispatch notification to controller/admin
            $approver = User::find($approvedBy);
            if ($approver !== null) {
                $approver->notify(new PayRunApprovedNotification(
                    payRunId: (string) $run->id,
                    approvedBy: $approver->username,
                    payPeriodStart: $run->pay_period_start->toDateString(),
                    payPeriodEnd: $run->pay_period_end->toDateString(),
                ));
            }

            return $run->fresh(['payrollStatements']) ?? $run;
        });
    }

    public function finalize(PayRun $payRun): PayRun
    {
        return DB::transaction(function () use ($payRun): PayRun {
            $run = $payRun->fresh(['payrollStatements']) ?? $payRun;

            if ($run->status !== PayRunStatus::Approved) {
                throw new DomainException('Pay run must be approved before it can be finalized.');
            }

            if ($run->approved_by === null) {
                throw new DomainException('Pay run approval must include an approver before finalization.');
            }

            $this->transition($run, PayRunStatus::Finalized);
            $run->finalized_at = now();
            $run->save();

            $this->auditTrail->record('payroll.pay-runs.finalized', $run, [
                'after' => [
                    'status' => $run->status->value,
                    'finalized_at' => $run->finalized_at?->toDateTimeString(),
                ],
            ]);

            // Notify controller and payroll admin
            $approver = User::find($run->approved_by);
            if ($approver !== null) {
                $approver->notify(new PayRunFinalizedNotification(
                    payRunId: (string) $run->id,
                    payPeriodStart: $run->pay_period_start->toDateString(),
                    payPeriodEnd: $run->pay_period_end->toDateString(),
                    payDate: $run->pay_date->toDateString(),
                    employeeCount: $run->employee_count,
                ));
            }

            return $run->fresh(['payrollStatements']) ?? $run;
        });
    }

    public function voidRun(PayRun $payRun): PayRun
    {
        return DB::transaction(function () use ($payRun): PayRun {
            $run = $payRun->fresh();

            if ($run === null) {
                throw new DomainException('Pay run was not found.');
            }

            if ($run->status !== PayRunStatus::Finalized) {
                throw new DomainException('Only finalized pay runs may be voided.');
            }

            PayRun::query()
                ->whereKey($run->id)
                ->where('status', PayRunStatus::Finalized->value)
                ->update(['status' => PayRunStatus::Void->value]);

            $this->auditTrail->record('payroll.pay-runs.voided', $run, [
                'before' => ['status' => PayRunStatus::Finalized->value],
                'after' => ['status' => PayRunStatus::Void->value],
            ]);

            // Notify controller and system admin of void action (critical)
            $approver = User::find($run->approved_by);
            if ($approver !== null) {
                $approver->notify(new PayRunVoidedNotification(
                    payRunId: (string) $run->id,
                    payPeriodStart: $run->pay_period_start->toDateString(),
                    payPeriodEnd: $run->pay_period_end->toDateString(),
                ));
            }

            return $run->fresh(['payrollStatements']) ?? $run;
        });
    }

    public function recalculateTotals(PayRun $payRun): PayRun
    {
        $totals = $payRun->payrollStatements()
            ->selectRaw('COALESCE(SUM(gross_pay), 0) as gross_total')
            ->selectRaw('COALESCE(SUM(net_pay), 0) as net_total')
            ->selectRaw('COALESCE(SUM(federal_tax + state_tax + local_tax + social_security + medicare), 0) as tax_total')
            ->selectRaw('COUNT(*) as statement_count')
            ->first();

        $payRun->fill([
            'total_gross' => round((float) ($totals?->gross_total ?? 0.0), 2),
            'total_net' => round((float) ($totals?->net_total ?? 0.0), 2),
            'total_taxes' => round((float) ($totals?->tax_total ?? 0.0), 2),
            'employee_count' => (int) ($totals?->statement_count ?? 0),
        ]);

        $payRun->save();

        return $payRun;
    }

    private function transition(PayRun $payRun, PayRunStatus $next): void
    {
        $current = $payRun->status;

        if (! $current->canTransitionTo($next)) {
            throw new DomainException("Invalid pay run status transition from [{$current->value}] to [{$next->value}].");
        }

        $payRun->status = $next;
    }
}
