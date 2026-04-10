<?php

namespace App\Domains\Payroll\Services;

use App\Core\Identity\Models\User;
use App\Domains\Payroll\Models\PayrollPeriod;
use App\Domains\Payroll\Models\PayrollRecord;
use App\Domains\Payroll\Models\PayRun;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PayrollProcessingService
{
    public function __construct(
        private readonly PayrollCalculationService $calculationService,
    ) {}

    /**
     * Create a new payroll period.
     */
    public function createPayrollPeriod(
        string $periodStartDate,
        string $periodEndDate,
        User $createdBy,
        ?string $notes = null
    ): PayrollPeriod {
        return DB::transaction(function () use ($periodStartDate, $periodEndDate, $createdBy, $notes) {
            return PayrollPeriod::create([
                'period_start_date' => $periodStartDate,
                'period_end_date' => $periodEndDate,
                'status' => PayrollPeriod::STATUS_OPEN,
                'notes' => $notes,
                'created_by' => $createdBy->id,
            ]);
        });
    }

    /**
     * Lock a payroll period to prevent further modifications.
     *
     *
     * @throws ValidationException
     */
    public function lockPayrollPeriod(PayrollPeriod $period): void
    {
        if ($period->status !== PayrollPeriod::STATUS_OPEN) {
            throw ValidationException::withMessages([
                'status' => 'Only open periods can be locked',
            ]);
        }

        $period->update(['status' => PayrollPeriod::STATUS_LOCKED]);
    }

    /**
     * Create a provisional pay run for a payroll period.
     *
     * @param  array  $users  Array of users to include in payrun
     *
     * @throws ValidationException
     */
    public function createProvisionalPayRun(
        PayrollPeriod $period,
        array $users,
        User $createdBy
    ): PayRun {
        if ($period->status !== PayrollPeriod::STATUS_OPEN) {
            throw ValidationException::withMessages([
                'period_status' => 'Period must be open to create new pay runs',
            ]);
        }

        return DB::transaction(function () use ($period, $users, $createdBy) {
            $payRun = PayRun::create([
                'payroll_period_id' => $period->id,
                'status' => PayRun::STATUS_PROVISIONAL,
                'created_by' => $createdBy->id,
            ]);

            // Create payroll records for each user
            $records = [];
            foreach ($users as $user) {
                $calculation = $this->calculationService->calculatePayroll(
                    $user,
                    regularHours: 40,
                    overtimeHours: 0
                );

                $validation = $this->calculationService->validateCalculation($calculation);
                if (! $validation['valid']) {
                    throw ValidationException::withMessages([
                        'user_'.$user->id => 'Invalid calculation for user',
                    ]);
                }

                $records[] = PayrollRecord::create([
                    'pay_run_id' => $payRun->id,
                    'user_id' => $user->id,
                    ...$calculation,
                    'created_by' => $createdBy->id,
                ]);
            }

            // Update pay run totals
            $totals = $this->calculationService->aggregatePayroll($records);
            $payRun->update([
                ...$totals,
                'records_count' => count($records),
            ]);

            return $payRun;
        });
    }

    /**
     * Approve a provisional pay run.
     *
     *
     * @throws ValidationException
     */
    public function approvePayRun(PayRun $payRun, User $approvedBy): void
    {
        if ($payRun->status !== PayRun::STATUS_PROVISIONAL) {
            throw ValidationException::withMessages([
                'status' => 'Only provisional pay runs can be approved',
            ]);
        }

        DB::transaction(function () use ($payRun, $approvedBy) {
            $payRun->update([
                'status' => PayRun::STATUS_APPROVED,
                'approved_by' => $approvedBy->id,
                'approved_at' => now(),
            ]);
        });
    }

    /**
     * Finalize a payroll period (mark all runs as final).
     *
     *
     * @throws ValidationException
     */
    public function finalizePayrollPeriod(PayrollPeriod $period, User $finalizedBy): void
    {
        if ($period->status !== PayrollPeriod::STATUS_LOCKED) {
            throw ValidationException::withMessages([
                'status' => 'Only locked periods can be finalized',
            ]);
        }

        $hasUnapprovedRuns = $period->payRuns()
            ->whereIn('status', [PayRun::STATUS_DRAFT, PayRun::STATUS_PROVISIONAL])
            ->exists();

        if ($hasUnapprovedRuns) {
            throw ValidationException::withMessages([
                'pay_runs' => 'All pay runs must be approved before finalization',
            ]);
        }

        DB::transaction(function () use ($period, $finalizedBy) {
            // Update period status
            $period->update([
                'status' => PayrollPeriod::STATUS_FINALIZED,
                'finalized_by' => $finalizedBy->id,
                'finalized_at' => now(),
            ]);

            // Update all pay runs to final status
            $period->payRuns()
                ->whereIn('status', [PayRun::STATUS_APPROVED])
                ->update([
                    'status' => PayRun::STATUS_FINAL,
                    'updated_by' => $finalizedBy->id,
                ]);
        });
    }

    /**
     * Check if a period can be reopened for corrections.
     */
    public function canReopenForCorrections(PayrollPeriod $period): bool
    {
        // A finalized period can be reopened if no payroll has been distributed
        // This is a placeholder - implement actual business logic as needed
        return $period->status === PayrollPeriod::STATUS_FINALIZED;
    }

    /**
     * Reopen a finalized period for corrections.
     *
     *
     * @throws ValidationException
     */
    public function reopenPayrollPeriod(PayrollPeriod $period, User $reopenedBy): void
    {
        if (! $this->canReopenForCorrections($period)) {
            throw ValidationException::withMessages([
                'status' => 'Period cannot be reopened for corrections',
            ]);
        }

        DB::transaction(function () use ($period, $reopenedBy) {
            $period->update([
                'status' => PayrollPeriod::STATUS_LOCKED,
                'finalized_by' => null,
                'finalized_at' => null,
                'updated_by' => $reopenedBy->id,
            ]);
        });
    }
}
