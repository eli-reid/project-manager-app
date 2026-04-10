<?php

namespace App\Domains\Payroll\Services;

use App\Domains\Payroll\Models\PayrollPeriod;
use App\Domains\Payroll\Models\PayrollRecord;
use App\Domains\Payroll\Models\PayRun;

class PayrollReportService
{
    /**
     * Generate payroll summary for a period.
     */
    public function generatePeriodSummary(PayrollPeriod $period): array
    {
        $payRuns = $period->payRuns()->get();
        $records = PayrollRecord::whereIn('pay_run_id', $payRuns->pluck('id'))->get();

        $totals = [
            'total_gross' => 0,
            'total_deductions' => 0,
            'total_net' => 0,
            'total_federal_tax' => 0,
            'total_state_tax' => 0,
            'total_local_tax' => 0,
            'total_social_security' => 0,
            'total_medicare' => 0,
        ];

        foreach ($records as $record) {
            $totals['total_gross'] += $record->gross_amount;
            $totals['total_deductions'] += $record->total_deductions;
            $totals['total_net'] += $record->net_amount;
            $totals['total_federal_tax'] += $record->federal_tax;
            $totals['total_state_tax'] += $record->state_tax;
            $totals['total_local_tax'] += $record->local_tax;
            $totals['total_social_security'] += $record->social_security;
            $totals['total_medicare'] += $record->medicare;
        }

        return [
            'period' => [
                'start_date' => $period->period_start_date->toDateString(),
                'end_date' => $period->period_end_date->toDateString(),
                'status' => $period->status,
            ],
            'totals' => array_map(fn ($v) => round($v, 2), $totals),
            'record_count' => $records->count(),
            'pay_run_count' => $payRuns->count(),
        ];
    }

    /**
     * Generate CSV export for payroll records.
     */
    public function generatePayrollCSV(PayRun $payRun): string
    {
        $records = $payRun->payrollRecords()->with('employee')->get();

        $csv = "Employee,Regular Hours,Overtime Hours,Gross Amount,Federal Tax,State Tax,Local Tax,Social Security,Medicare,Total Deductions,Net Amount\n";

        foreach ($records as $record) {
            $row = [
                $record->employee->name,
                $record->regular_hours,
                $record->overtime_hours,
                $record->gross_amount,
                $record->federal_tax,
                $record->state_tax,
                $record->local_tax,
                $record->social_security,
                $record->medicare,
                $record->total_deductions,
                $record->net_amount,
            ];

            $csv .= $this->escapeCSVRow($row)."\n";
        }

        return $csv;
    }

    /**
     * Generate payroll summary data for display.
     */
    public function generatePayRunSummary(PayRun $payRun): array
    {
        $records = $payRun->payrollRecords()->get();

        $summary = [
            'pay_run_id' => $payRun->id,
            'status' => $payRun->status,
            'total_employees' => $records->count(),
            'total_gross' => round($payRun->total_gross, 2),
            'total_deductions' => round($payRun->total_deductions, 2),
            'total_net' => round($payRun->total_net, 2),
            'records' => $records->map(function (PayrollRecord $record) {
                return [
                    'employee_name' => $record->employee->name,
                    'employee_id' => $record->user_id,
                    'regular_hours' => $record->regular_hours,
                    'overtime_hours' => $record->overtime_hours,
                    'gross_amount' => round($record->gross_amount, 2),
                    'total_deductions' => round($record->total_deductions, 2),
                    'net_amount' => round($record->net_amount, 2),
                ];
            })->toArray(),
        ];

        return $summary;
    }

    /**
     * Get payroll statistics for a period.
     */
    public function getPayrollStatistics(PayrollPeriod $period): array
    {
        $payRuns = $period->payRuns();
        $records = PayrollRecord::whereIn('pay_run_id', $payRuns->pluck('id'))->get();

        if ($records->isEmpty()) {
            return $this->getEmptyStatistics();
        }

        $grossAmounts = $records->pluck('gross_amount')->toArray();
        $netAmounts = $records->pluck('net_amount')->toArray();
        $deductions = $records->pluck('total_deductions')->toArray();

        return [
            'gross_average' => round(array_sum($grossAmounts) / count($grossAmounts), 2),
            'gross_min' => round(min($grossAmounts), 2),
            'gross_max' => round(max($grossAmounts), 2),
            'net_average' => round(array_sum($netAmounts) / count($netAmounts), 2),
            'net_min' => round(min($netAmounts), 2),
            'net_max' => round(max($netAmounts), 2),
            'deduction_average' => round(array_sum($deductions) / count($deductions), 2),
            'employee_count' => $records->count(),
        ];
    }

    /**
     * Escape CSV row values.
     */
    private function escapeCSVRow(array $row): string
    {
        return implode(',', array_map(function ($value) {
            if (is_numeric($value)) {
                return $value;
            }

            $value = str_replace('"', '""', $value);

            return '"'.$value.'"';
        }, $row));
    }

    /**
     * Get empty statistics array.
     */
    private function getEmptyStatistics(): array
    {
        return [
            'gross_average' => 0,
            'gross_min' => 0,
            'gross_max' => 0,
            'net_average' => 0,
            'net_min' => 0,
            'net_max' => 0,
            'deduction_average' => 0,
            'employee_count' => 0,
        ];
    }
}
