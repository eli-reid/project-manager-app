<?php

namespace App\Domains\Payroll\Services;

use App\Domains\Payroll\Contracts\PayrollTimecardReadGateway;
use App\Domains\Payroll\Models\EmployeeDeduction;
use App\Domains\Payroll\Models\PayRate;
use App\Domains\Payroll\Models\PayRateType;
use App\Domains\Payroll\Models\PayrollStatement;
use App\Domains\Projects\Models\Project;
use App\Domains\Timecards\Models\TimecardEntry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class PayrollReportingService
{
    public function __construct(
        private readonly PayrollTimecardReadGateway $timecardReadGateway,
    ) {}

    /**
     * @return Collection<int, Project>
     */
    public function activeProjects(): Collection
    {
        return Project::query()
            ->where('is_active', true)
            ->orderBy('project_number')
            ->orderBy('name')
            ->get();
    }

    /**
     * @return array<int, array{employee:string,project:string,cost_code:string,classification:string,regular_hours:float,overtime_hours:float,double_time_hours:float,total_hours:float,base_rate:float,fringe_rate:float,gross_wages:float,fringe_due:float}>
     */
    public function certifiedPayrollRows(?string $projectId, Carbon $weekStarting): array
    {
        $weekStart = $weekStarting->copy()->startOfWeek();
        $weekEnd = $weekStart->copy()->addDays(6);

        $entries = $this->approvedEntriesQuery($projectId, $weekStart, $weekEnd)
            ->filter(function (TimecardEntry $entry): bool {
                return $entry->project_id !== null
                    && (
                        $entry->work_classification !== null
                        || $entry->prevailing_base_rate !== null
                        || $entry->prevailing_fringe_rate !== null
                    );
            })
            ->values();

        $grouped = $entries->groupBy(function (TimecardEntry $entry): string {
            $costCodeId = $entry->cost_code_id ?? 'none';
            $classification = trim((string) ($entry->work_classification ?? 'Unclassified'));

            return implode('|', [(string) $entry->user_id, (string) $entry->project_id, (string) $costCodeId, $classification]);
        });

        $rows = [];

        foreach ($grouped as $group) {
            $first = $group->first();
            if (! $first instanceof TimecardEntry) {
                continue;
            }

            $regularHours = (float) $group->sum(function (TimecardEntry $entry): float {
                if ($entry->regular_hours !== null) {
                    return (float) $entry->regular_hours;
                }

                if ($entry->overtime_hours !== null || $entry->double_time_hours !== null) {
                    $hours = (float) $entry->hours;
                    $ot = (float) ($entry->overtime_hours ?? 0);
                    $dt = (float) ($entry->double_time_hours ?? 0);

                    return max(0.0, $hours - $ot - $dt);
                }

                return (float) $entry->hours;
            });

            $overtimeHours = (float) $group->sum(fn (TimecardEntry $entry): float => (float) ($entry->overtime_hours ?? 0));
            $doubleTimeHours = (float) $group->sum(fn (TimecardEntry $entry): float => (float) ($entry->double_time_hours ?? 0));
            $totalHours = $regularHours + $overtimeHours + $doubleTimeHours;

            $baseRate = (float) $group
                ->filter(fn (TimecardEntry $entry): bool => (float) ($entry->prevailing_base_rate ?? 0) > 0)
                ->avg(fn (TimecardEntry $entry): float => (float) ($entry->prevailing_base_rate ?? 0));

            $fringeRate = (float) $group
                ->filter(fn (TimecardEntry $entry): bool => (float) ($entry->prevailing_fringe_rate ?? 0) > 0)
                ->avg(fn (TimecardEntry $entry): float => (float) ($entry->prevailing_fringe_rate ?? 0));

            $grossWages = round(($regularHours * $baseRate) + ($overtimeHours * $baseRate * 1.5) + ($doubleTimeHours * $baseRate * 2), 2);
            $fringeDue = round($totalHours * $fringeRate, 2);

            $rows[] = [
                'employee' => $this->employeeLabel($first),
                'project' => $this->projectLabel($first),
                'cost_code' => $this->costCodeLabel($first),
                'classification' => trim((string) ($first->work_classification ?? 'Unclassified')),
                'regular_hours' => round($regularHours, 2),
                'overtime_hours' => round($overtimeHours, 2),
                'double_time_hours' => round($doubleTimeHours, 2),
                'total_hours' => round($totalHours, 2),
                'base_rate' => round($baseRate, 4),
                'fringe_rate' => round($fringeRate, 4),
                'gross_wages' => $grossWages,
                'fringe_due' => $fringeDue,
            ];
        }

        usort($rows, function (array $left, array $right): int {
            if ($left['employee'] === $right['employee']) {
                return strcmp($left['cost_code'], $right['cost_code']);
            }

            return strcmp($left['employee'], $right['employee']);
        });

        return $rows;
    }

    /**
     * @return array<int, array{project:string,cost_code:string,employee:string,total_hours:float,estimated_labor_cost:float}>
     */
    public function laborCostRows(?string $projectId, ?string $fromDate, ?string $toDate): array
    {
        [$from, $to] = $this->normalizedDateRange($fromDate, $toDate);

        $entries = $this->approvedEntriesQuery($projectId, $from, $to);

        $standardRateMap = $this->buildStandardRateMap($entries, $to);

        $grouped = $entries->groupBy(function (TimecardEntry $entry): string {
            return implode('|', [
                (string) ($entry->project_id ?? 'none'),
                (string) ($entry->cost_code_id ?? 'none'),
                (string) $entry->user_id,
            ]);
        });

        $rows = [];

        foreach ($grouped as $group) {
            $first = $group->first();
            if (! $first instanceof TimecardEntry) {
                continue;
            }

            $totalHours = (float) $group->sum(function (TimecardEntry $entry): float {
                $regularHours = (float) ($entry->regular_hours ?? $entry->hours ?? 0);
                $overtimeHours = (float) ($entry->overtime_hours ?? 0);
                $doubleTimeHours = (float) ($entry->double_time_hours ?? 0);

                return $regularHours + $overtimeHours + $doubleTimeHours;
            });

            $cost = 0.0;

            foreach ($group as $entry) {
                $hourlyRate = (float) ($entry->prevailing_base_rate ?? 0);

                if ($hourlyRate <= 0) {
                    $hourlyRate = $standardRateMap[$entry->user_id] ?? 0.0;
                }

                $regularHours = (float) ($entry->regular_hours ?? $entry->hours ?? 0);
                $overtimeHours = (float) ($entry->overtime_hours ?? 0);
                $doubleTimeHours = (float) ($entry->double_time_hours ?? 0);

                $cost += ($regularHours * $hourlyRate)
                    + ($overtimeHours * $hourlyRate * 1.5)
                    + ($doubleTimeHours * $hourlyRate * 2);
            }

            $rows[] = [
                'project' => $this->projectLabel($first),
                'cost_code' => $this->costCodeLabel($first),
                'employee' => $this->employeeLabel($first),
                'total_hours' => round($totalHours, 2),
                'estimated_labor_cost' => round($cost, 2),
            ];
        }

        usort($rows, fn (array $left, array $right): int => $right['estimated_labor_cost'] <=> $left['estimated_labor_cost']);

        return $rows;
    }

    /**
     * @return array<int, array{pay_run:string,employee:string,union_code:string,deduction:string,total_hours:float,gross_pay:float,remittance_due:float}>
     */
    public function unionRemittanceRows(?string $unionCode, ?string $fromDate, ?string $toDate): array
    {
        [$from, $to] = $this->normalizedDateRange($fromDate, $toDate);

        $statements = PayrollStatement::query()
            ->with([
                'user:id,first_name,last_name,username',
                'payRun:id,pay_period_start,pay_period_end,pay_date',
                'payrollEmployeeProfile:id,user_id,union_code',
            ])
            ->whereHas('payRun', function (Builder $query) use ($from, $to): void {
                $query->whereDate('pay_period_end', '>=', $from->toDateString())
                    ->whereDate('pay_period_end', '<=', $to->toDateString());
            })
            ->when($unionCode !== null && $unionCode !== '', function (Builder $query) use ($unionCode): void {
                $query->whereHas('payrollEmployeeProfile', function (Builder $profileQuery) use ($unionCode): void {
                    $profileQuery->where('union_code', $unionCode);
                });
            })
            ->get();

        if ($statements->isEmpty()) {
            return [];
        }

        $profileIds = $statements
            ->pluck('payroll_employee_profile_id')
            ->filter()
            ->unique()
            ->values();

        $employeeDeductions = EmployeeDeduction::query()
            ->with('deduction:id,name,category,calculation_method,amount')
            ->whereIn('payroll_employee_profile_id', $profileIds)
            ->where('status', 'active')
            ->where(function (Builder $query) use ($to): void {
                $query->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', $to->toDateString());
            })
            ->whereDate('effective_date', '<=', $to->toDateString())
            ->get()
            ->groupBy('payroll_employee_profile_id');

        $rows = [];

        foreach ($statements as $statement) {
            $profile = $statement->payrollEmployeeProfile;
            if ($profile === null) {
                continue;
            }

            $deductions = $employeeDeductions->get($profile->id, collect())
                ->filter(function (EmployeeDeduction $employeeDeduction): bool {
                    $deduction = $employeeDeduction->deduction;
                    if ($deduction === null) {
                        return false;
                    }

                    if (strtolower((string) $deduction->category) === 'union') {
                        return true;
                    }

                    return str_contains(strtolower((string) $deduction->name), 'union');
                });

            foreach ($deductions as $employeeDeduction) {
                $deduction = $employeeDeduction->deduction;
                if ($deduction === null) {
                    continue;
                }

                $hours = (float) $statement->total_regular_hours
                    + (float) $statement->total_ot_hours
                    + (float) $statement->total_dt_hours;

                $baseAmount = (float) ($employeeDeduction->override_amount ?? $deduction->amount);

                $remittanceDue = match (strtolower((string) $deduction->calculation_method)) {
                    'percent', 'percentage' => ((float) $statement->gross_pay) * ($baseAmount / 100),
                    'hourly', 'per_hour' => $hours * $baseAmount,
                    default => $baseAmount,
                };

                $rows[] = [
                    'pay_run' => $statement->payRun?->pay_period_end?->toDateString() ?? 'N/A',
                    'employee' => $this->statementEmployeeLabel($statement),
                    'union_code' => (string) ($profile->union_code ?: 'N/A'),
                    'deduction' => (string) $deduction->name,
                    'total_hours' => round($hours, 2),
                    'gross_pay' => round((float) $statement->gross_pay, 2),
                    'remittance_due' => round($remittanceDue, 2),
                ];
            }
        }

        usort($rows, function (array $left, array $right): int {
            if ($left['pay_run'] === $right['pay_run']) {
                if ($left['union_code'] === $right['union_code']) {
                    return strcmp($left['employee'], $right['employee']);
                }

                return strcmp($left['union_code'], $right['union_code']);
            }

            return strcmp($right['pay_run'], $left['pay_run']);
        });

        return $rows;
    }

    /**
     * @return Collection<int, string>
     */
    public function availableUnionCodes(): Collection
    {
        return PayrollStatement::query()
            ->join('payroll_employee_profiles', 'payroll_employee_profiles.id', '=', 'payroll_statements.payroll_employee_profile_id')
            ->whereNotNull('payroll_employee_profiles.union_code')
            ->where('payroll_employee_profiles.union_code', '!=', '')
            ->distinct()
            ->orderBy('payroll_employee_profiles.union_code')
            ->pluck('payroll_employee_profiles.union_code');
    }

    /**
     * @return Collection<int, TimecardEntry>
     */
    private function approvedEntriesQuery(?string $projectId, Carbon $from, Carbon $to): Collection
    {
        return $this->timecardReadGateway->approvedEntriesForDateRange(
            startDate: $from,
            endDate: $to,
            projectId: $projectId,
            with: [
                'user:id,first_name,last_name,username',
                'user.payrollProfile:id,user_id',
                'project:id,name,project_number,wage_determination_id,is_prevailing_wage',
                'costCode:id,code,description',
                'timecard:id,status',
            ],
        );
    }

    /**
     * @param  Collection<int, TimecardEntry>  $entries
     * @return array<string, float>
     */
    private function buildStandardRateMap(Collection $entries, Carbon $workDate): array
    {
        $userIds = $entries->pluck('user_id')->unique()->values();

        if ($userIds->isEmpty()) {
            return [];
        }

        $profiles = $entries
            ->pluck('user.payrollProfile')
            ->filter()
            ->keyBy('user_id');

        if ($profiles->isEmpty()) {
            return [];
        }

        $standardTypeId = PayRateType::query()
            ->where('key', 'standard')
            ->value('id');

        if ($standardTypeId === null) {
            return [];
        }

        $rates = PayRate::query()
            ->whereIn('payroll_employee_profile_id', $profiles->pluck('id')->values())
            ->where('pay_rate_type_id', $standardTypeId)
            ->whereDate('effective_date', '<=', $workDate->toDateString())
            ->where(function (Builder $query) use ($workDate): void {
                $query->whereNull('expiration_date')
                    ->orWhereDate('expiration_date', '>=', $workDate->toDateString());
            })
            ->orderByDesc('effective_date')
            ->get()
            ->groupBy('payroll_employee_profile_id')
            ->map(fn (Collection $profileRates): ?PayRate => $profileRates->first())
            ->filter();

        $map = [];

        foreach ($profiles as $userId => $profile) {
            $rate = $rates->get($profile->id);
            if (! $rate instanceof PayRate) {
                continue;
            }

            $map[(string) $userId] = (float) $rate->rate_amount;
        }

        return $map;
    }

    private function employeeLabel(TimecardEntry $entry): string
    {
        $firstName = (string) ($entry->user?->first_name ?? '');
        $lastName = (string) ($entry->user?->last_name ?? '');
        $fullName = trim($firstName.' '.$lastName);

        if ($fullName !== '') {
            return $fullName;
        }

        return (string) ($entry->user?->username ?? 'Unknown');
    }

    private function statementEmployeeLabel(PayrollStatement $statement): string
    {
        $firstName = (string) ($statement->user?->first_name ?? '');
        $lastName = (string) ($statement->user?->last_name ?? '');
        $fullName = trim($firstName.' '.$lastName);

        if ($fullName !== '') {
            return $fullName;
        }

        return (string) ($statement->user?->username ?? 'Unknown');
    }

    private function projectLabel(TimecardEntry $entry): string
    {
        $project = $entry->project;

        if ($project === null) {
            return 'No Project';
        }

        return $project->project_number
            ? $project->project_number.' - '.$project->name
            : $project->name;
    }

    private function costCodeLabel(TimecardEntry $entry): string
    {
        $costCode = $entry->costCode;

        if ($costCode === null) {
            return 'Unassigned';
        }

        return trim($costCode->code.' - '.$costCode->description);
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function normalizedDateRange(?string $fromDate, ?string $toDate): array
    {
        $from = filled($fromDate) ? Carbon::parse($fromDate)->startOfDay() : now()->startOfMonth();
        $to = filled($toDate) ? Carbon::parse($toDate)->endOfDay() : now()->endOfDay();

        if ($from->greaterThan($to)) {
            return [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        return [$from, $to];
    }
}
