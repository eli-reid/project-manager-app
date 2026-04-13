<?php

namespace App\Domains\Payroll\Services;

use App\Domains\Payroll\Contracts\PayrollTimecardReadGateway;
use App\Domains\Payroll\Data\ValidationResult;
use App\Domains\Payroll\Data\ValidationViolation;
use App\Domains\Payroll\Enums\ValidationSeverity;
use App\Domains\Payroll\Models\PayrollEmployeeProfile;
use App\Domains\Timecards\Models\TimecardEntry;
use Illuminate\Support\Carbon;

class TimecardEntryValidationService
{
    public function __construct(
        protected PayPeriodService $payPeriodService,
        protected PayrollRateResolutionService $rateResolutionService,
        protected PayrollTimecardReadGateway $timecardReadGateway,
    ) {}

    /**
     * Run all validation rules (V-01 – V-10) against a timecard entry.
     */
    public function validate(TimecardEntry $entry): ValidationResult
    {
        $violations = [];

        // V-01 — Employee must have an active payroll profile.
        $profile = $this->findProfile($entry->user_id);

        if ($profile === null || $profile->status !== 'active') {
            $violations[] = new ValidationViolation(
                ruleId: 'V-01',
                severity: ValidationSeverity::Block,
                message: 'Employee does not have an active payroll profile.',
            );
        }

        // V-02 — Project must be active (not in a closed/cancelled state).
        $project = $entry->project;

        if ($project === null || $project->status->isInactive()) {
            $violations[] = new ValidationViolation(
                ruleId: 'V-02',
                severity: ValidationSeverity::Block,
                message: 'Project is not active.',
            );
        }

        // V-03 — Cost code must belong to the specified project.
        if ($entry->cost_code_id !== null) {
            $costCode = $entry->costCode;

            if ($costCode === null || (string) $costCode->project_id !== (string) $entry->project_id) {
                $violations[] = new ValidationViolation(
                    ruleId: 'V-03',
                    severity: ValidationSeverity::Block,
                    message: 'Cost code does not belong to the specified project.',
                );
            }
        }

        // V-04 — Work date must not be in the future.
        $workDate = Carbon::instance($entry->date);

        if ($workDate->isFuture()) {
            $violations[] = new ValidationViolation(
                ruleId: 'V-04',
                severity: ValidationSeverity::Block,
                message: 'Work date may not be in the future.',
            );
        }

        // V-05 — Work date must be within the current or prior pay period.
        if (! $this->payPeriodService->isWithinCurrentOrPriorPeriod($workDate)) {
            $violations[] = new ValidationViolation(
                ruleId: 'V-05',
                severity: ValidationSeverity::Block,
                message: 'Work date must be within the current or prior pay period.',
            );
        }

        // V-06 / V-07 — Daily hour totals (Block > 24 h, Warning > 16 h).
        $dailyTotal = $this->computeDailyTotal($entry);

        if ($dailyTotal > 24.0) {
            $violations[] = new ValidationViolation(
                ruleId: 'V-06',
                severity: ValidationSeverity::Block,
                message: 'Total hours logged for this date would exceed 24.',
            );
        } elseif ($dailyTotal > 16.0) {
            $violations[] = new ValidationViolation(
                ruleId: 'V-07',
                severity: ValidationSeverity::Warning,
                message: 'Total hours logged for this date exceeds 16.',
            );
        }

        // V-08 — Duplicate entry (same employee/date/project/cost code).
        if ($this->isDuplicate($entry)) {
            $violations[] = new ValidationViolation(
                ruleId: 'V-08',
                severity: ValidationSeverity::Warning,
                message: 'A timecard entry already exists for this employee, date, project, and cost code.',
            );
        }

        // V-09 — Employee has no approved pay rate for the specified project.
        if ($profile !== null && $entry->project_id !== null) {
            if (! $this->rateResolutionService->hasAnyRate($profile, $entry->project_id, $workDate)) {
                $violations[] = new ValidationViolation(
                    ruleId: 'V-09',
                    severity: ValidationSeverity::Warning,
                    message: 'Employee has no approved pay rate applicable to this project on the work date.',
                );
            }
        }

        // V-10 — Prior-period entry submitted after the pay-period cut-off.
        if (! $workDate->isFuture() && $this->payPeriodService->isBeyondCutOff($workDate)) {
            $violations[] = new ValidationViolation(
                ruleId: 'V-10',
                severity: ValidationSeverity::Warning,
                message: 'Entry submitted after pay-period cut-off; admin override required.',
            );
        }

        return new ValidationResult($violations);
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    /**
     * Return the employee's payroll profile, or null if none exists.
     */
    protected function findProfile(string $userId): ?PayrollEmployeeProfile
    {
        return PayrollEmployeeProfile::query()
            ->where('user_id', $userId)
            ->first();
    }

    /**
     * Sum all existing entries for the same employee+date, then add this entry's
     * hours to produce the projected daily total.
     */
    protected function computeDailyTotal(TimecardEntry $entry): float
    {
        $existing = $this->timecardReadGateway->existingHoursForUserOnDate(
            userId: $entry->user_id,
            date: Carbon::instance($entry->date),
            excludeEntryId: $entry->exists ? (string) $entry->id : null,
        );

        return (float) $existing + (float) $entry->hours;
    }

    /**
     * Returns true when another entry already exists for the same
     * employee / date / project / cost code combination.
     */
    protected function isDuplicate(TimecardEntry $entry): bool
    {
        return $this->timecardReadGateway->duplicateEntryExists(
            userId: $entry->user_id,
            date: Carbon::instance($entry->date),
            projectId: $entry->project_id ? (string) $entry->project_id : null,
            costCodeId: $entry->cost_code_id ? (string) $entry->cost_code_id : null,
            excludeEntryId: $entry->exists ? (string) $entry->id : null,
        );
    }
}
