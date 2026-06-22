<?php

namespace App\Domains\Payroll\Services;

use App\Domains\Payroll\Contracts\ApprovedTimecardEntryProvider;
use App\Domains\Payroll\Enums\OvertimeRule;
use App\Domains\Payroll\Models\EmployeeDeduction;
use App\Domains\Payroll\Models\PayrollEmployeeProfile;
use App\Domains\Payroll\Models\PayrollStatement;
use App\Domains\Payroll\Models\PayRun;
use App\Domains\Timecards\Models\TimecardEntry;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class PayrollStatementBuilderService
{
    public function __construct(
        private readonly ApprovedTimecardEntryProvider $approvedTimecardEntryProvider,
        private readonly PayrollRateResolutionService $rateResolutionService,
        private readonly OvertimeCalculationService $overtimeCalculationService,
        private readonly TaxWithholdingService $taxWithholdingService,
        private readonly GrossToNetService $grossToNetService,
    ) {}

    /**
     * @return Collection<int, PayrollStatement>
     */
    public function buildForRun(PayRun $payRun): Collection
    {
        PayrollStatement::query()->where('pay_run_id', $payRun->id)->delete();

        $entries = $this->approvedTimecardEntryProvider->forPayPeriod(
            $payRun->pay_period_start,
            $payRun->pay_period_end,
        );

        if ($entries->isEmpty()) {
            return collect();
        }

        $profilesByUserId = PayrollEmployeeProfile::query()
            ->whereIn('user_id', $entries->pluck('user_id')->unique()->values())
            ->get()
            ->keyBy('user_id');

        return $entries
            ->groupBy('user_id')
            ->map(function (Collection $userEntries, string $userId) use ($profilesByUserId, $payRun): ?PayrollStatement {
                /** @var PayrollEmployeeProfile|null $profile */
                $profile = $profilesByUserId->get($userId);

                if ($profile === null) {
                    return null;
                }

                $hoursByEntry = $this->hoursByEntry($userEntries);
                $hourTotals = $this->hourTotals($hoursByEntry);
                $grossPay = $this->grossPay($profile, $userEntries, $hoursByEntry);
                $deductions = $this->deductions($profile, $payRun, $hourTotals['total_hours'], $grossPay);
                $taxes = $this->taxWithholdingService->calculate(max(0.0, $grossPay - $deductions['pre_tax']));
                $grossToNet = $this->grossToNetService->calculate($grossPay, $deductions['pre_tax'], $deductions['post_tax'], $taxes);
                $ytd = $this->ytdAmounts($profile, $payRun, $grossPay, (float) $taxes['federal'], (float) $grossToNet['net_pay']);

                return PayrollStatement::query()->create([
                    'user_id' => $profile->user_id,
                    'payroll_employee_profile_id' => $profile->id,
                    'pay_run_id' => $payRun->id,
                    'total_regular_hours' => $hourTotals['regular_hours'],
                    'total_ot_hours' => $hourTotals['ot_hours'],
                    'total_dt_hours' => $hourTotals['dt_hours'],
                    'gross_pay' => $grossPay,
                    'federal_tax' => (float) $taxes['federal'],
                    'state_tax' => (float) $taxes['state'],
                    'local_tax' => (float) $taxes['local'],
                    'social_security' => (float) $taxes['social_security'],
                    'medicare' => (float) $taxes['medicare'],
                    'other_deductions' => round($deductions['pre_tax'] + $deductions['post_tax'], 2),
                    'net_pay' => (float) $grossToNet['net_pay'],
                    'ytd_gross' => $ytd['gross'],
                    'ytd_federal_tax' => $ytd['federal_tax'],
                    'ytd_net' => $ytd['net'],
                ]);
            })
            ->filter()
            ->values();
    }

    /**
     * @param  Collection<int, TimecardEntry>  $entries
     * @return array<string, array{regular:float,ot:float,dt:float,total:float}>
     */
    private function hoursByEntry(Collection $entries): array
    {
        $explicitBreakdown = $entries->contains(function (TimecardEntry $entry): bool {
            return (float) ($entry->regular_hours ?? 0.0) > 0.0
                || (float) ($entry->overtime_hours ?? 0.0) > 0.0
                || (float) ($entry->double_time_hours ?? 0.0) > 0.0;
        });

        if ($explicitBreakdown) {
            return $entries->mapWithKeys(function (TimecardEntry $entry): array {
                $regular = (float) ($entry->regular_hours ?? $entry->hours);
                $ot = (float) ($entry->overtime_hours ?? 0.0);
                $dt = (float) ($entry->double_time_hours ?? 0.0);

                return [
                    (string) $entry->id => [
                        'regular' => $regular,
                        'ot' => $ot,
                        'dt' => $dt,
                        'total' => round($regular + $ot + $dt, 2),
                    ],
                ];
            })->all();
        }

        $breakdown = $this->overtimeCalculationService->calculate($entries, OvertimeRule::WeeklyFlsa);

        return collect($breakdown)->mapWithKeys(function ($row, string $entryId): array {
            return [
                $entryId => [
                    'regular' => round((float) $row->regularHours, 2),
                    'ot' => round((float) $row->overtimeHours, 2),
                    'dt' => round((float) $row->doubleTimeHours, 2),
                    'total' => round((float) $row->totalHours(), 2),
                ],
            ];
        })->all();
    }

    /**
     * @param  array<string, array{regular:float,ot:float,dt:float,total:float}>  $hoursByEntry
     * @return array{regular_hours:float,ot_hours:float,dt_hours:float,total_hours:float}
     */
    private function hourTotals(array $hoursByEntry): array
    {
        $regularHours = round((float) collect($hoursByEntry)->sum('regular'), 2);
        $otHours = round((float) collect($hoursByEntry)->sum('ot'), 2);
        $dtHours = round((float) collect($hoursByEntry)->sum('dt'), 2);

        return [
            'regular_hours' => $regularHours,
            'ot_hours' => $otHours,
            'dt_hours' => $dtHours,
            'total_hours' => round($regularHours + $otHours + $dtHours, 2),
        ];
    }

    /**
     * @param  Collection<int, TimecardEntry>  $entries
     * @param  array<string, array{regular:float,ot:float,dt:float,total:float}>  $hoursByEntry
     */
    private function grossPay(PayrollEmployeeProfile $profile, Collection $entries, array $hoursByEntry): float
    {
        $gross = 0.0;

        foreach ($entries as $entry) {
            $entryId = (string) $entry->id;
            $hours = $hoursByEntry[$entryId] ?? ['regular' => 0.0, 'ot' => 0.0, 'dt' => 0.0, 'total' => 0.0];
            $workDate = Carbon::parse($entry->date);

            $standardRate = $this->rateResolutionService->resolveForProject(
                $profile,
                $entry->project_id ? (string) $entry->project_id : null,
                $workDate,
            );

            $baseRate = (float) ($standardRate?->rate_amount ?? 0.0);

            $gross += $hours['regular'] * $baseRate;
            $gross += $hours['ot'] * $baseRate * 1.5;
            $gross += $hours['dt'] * $baseRate * 2.0;

            $fringeRate = $this->rateResolutionService->resolve(
                $profile,
                'prevailing_fringe',
                $entry->project_id ? (string) $entry->project_id : null,
                $workDate,
            );

            if ($fringeRate !== null) {
                $gross += $hours['total'] * (float) $fringeRate->rate_amount;
            }
        }

        return round($gross, 2);
    }

    /**
     * @return array{pre_tax:float,post_tax:float}
     */
    private function deductions(PayrollEmployeeProfile $profile, PayRun $payRun, float $totalHours, float $grossPay): array
    {
        $deductions = EmployeeDeduction::query()
            ->with('deduction:id,amount,calculation_method,pre_tax,priority')
            ->where('payroll_employee_profile_id', $profile->id)
            ->where('status', 'active')
            ->whereDate('effective_date', '<=', $payRun->pay_period_end->toDateString())
            ->where(function ($query) use ($payRun): void {
                $query->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', $payRun->pay_period_start->toDateString());
            })
            ->get()
            ->sortBy(fn (EmployeeDeduction $row): int => (int) ($row->deduction?->priority ?? 999));

        $preTax = 0.0;
        $postTax = 0.0;

        foreach ($deductions as $employeeDeduction) {
            $deduction = $employeeDeduction->deduction;

            if ($deduction === null) {
                continue;
            }

            $baseAmount = (float) ($employeeDeduction->override_amount ?? $deduction->amount ?? 0.0);
            $amount = match ($deduction->calculation_method) {
                'hourly' => $baseAmount * $totalHours,
                'percentage' => $grossPay * ($baseAmount / 100),
                default => $baseAmount,
            };

            if ($deduction->pre_tax) {
                $preTax += $amount;
            } else {
                $postTax += $amount;
            }
        }

        return [
            'pre_tax' => round(max(0.0, $preTax), 2),
            'post_tax' => round(max(0.0, $postTax), 2),
        ];
    }

    /**
     * @return array{gross:float,federal_tax:float,net:float}
     */
    private function ytdAmounts(PayrollEmployeeProfile $profile, PayRun $payRun, float $grossPay, float $federalTax, float $netPay): array
    {
        $yearStart = $payRun->pay_date->copy()->startOfYear()->toDateString();
        $currentPayDate = $payRun->pay_date->toDateString();

        $historical = PayrollStatement::query()
            ->where('payroll_employee_profile_id', $profile->id)
            ->whereHas('payRun', function ($query) use ($yearStart, $currentPayDate): void {
                $query->whereDate('pay_date', '>=', $yearStart)
                    ->whereDate('pay_date', '<', $currentPayDate);
            })
            ->selectRaw('COALESCE(SUM(gross_pay),0) as gross_sum, COALESCE(SUM(federal_tax),0) as federal_sum, COALESCE(SUM(net_pay),0) as net_sum')
            ->first();

        $priorGross = (float) ($historical?->gross_sum ?? 0.0);
        $priorFederalTax = (float) ($historical?->federal_sum ?? 0.0);
        $priorNet = (float) ($historical?->net_sum ?? 0.0);

        return [
            'gross' => round($priorGross + $grossPay, 2),
            'federal_tax' => round($priorFederalTax + $federalTax, 2),
            'net' => round($priorNet + $netPay, 2),
        ];
    }
}
