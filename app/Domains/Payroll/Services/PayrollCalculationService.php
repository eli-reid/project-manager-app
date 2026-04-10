<?php

namespace App\Domains\Payroll\Services;

use App\Core\Identity\Models\User;
use App\Domains\Payroll\Models\BurdenRate;
use App\Domains\Payroll\Models\PayRate;
use App\Domains\Payroll\Models\PayrollRecord;
use Illuminate\Support\Collection;

class PayrollCalculationService
{
    /**
     * Calculate payroll for a user based on hours and rates.
     *
     * @param  Collection<int, BurdenRate>|null  $burdenRates
     * @return array<string, float>
     */
    public function calculatePayroll(
        User $user,
        float $regularHours,
        float $overtimeHours,
        ?PayRate $payRate = null,
        ?Collection $burdenRates = null
    ): array {
        // Load pay rate if not provided
        if ($payRate === null) {
            $payRate = PayRate::forUser($user->id)
                ->activeOn()
                ->latest('effective_date')
                ->first();
        }

        if ($payRate === null) {
            throw new \InvalidArgumentException('No active pay rate found for user');
        }

        // Load burden rates if not provided
        if ($burdenRates === null) {
            $burdenRates = BurdenRate::activeOn()->get();
        }

        // Calculate gross amount
        $overtimeMultiplier = 1.5; // Standard 1.5x overtime
        $regularPay = $regularHours * $payRate->rate;
        $overtimePay = $overtimeHours * $payRate->rate * $overtimeMultiplier;
        $grossAmount = $regularPay + $overtimePay;

        // Calculate taxes and deductions
        $federalTax = $this->calculateTax($grossAmount, 'federal_tax', $burdenRates);
        $stateTax = $this->calculateTax($grossAmount, 'state_tax', $burdenRates);
        $localTax = $this->calculateTax($grossAmount, 'local_tax', $burdenRates);
        $socialSecurity = $this->calculateTax($grossAmount, 'social_security', $burdenRates);
        $medicare = $this->calculateTax($grossAmount, 'medicare', $burdenRates);

        // Total deductions
        $totalDeductions = $federalTax + $stateTax + $localTax + $socialSecurity + $medicare;

        // Net amount
        $netAmount = round($grossAmount - $totalDeductions, 2);

        return [
            'regular_hours' => $regularHours,
            'overtime_hours' => $overtimeHours,
            'gross_amount' => round($grossAmount, 2),
            'federal_tax' => $federalTax,
            'state_tax' => $stateTax,
            'local_tax' => $localTax,
            'social_security' => $socialSecurity,
            'medicare' => $medicare,
            'total_deductions' => round($totalDeductions, 2),
            'net_amount' => $netAmount,
        ];
    }

    /**
     * Calculate a specific tax component based on burden rates.
     *
     * @param  Collection<BurdenRate>  $burdenRates
     */
    private function calculateTax(float $grossAmount, string $component, Collection $burdenRates): float
    {
        $rates = $burdenRates->filter(fn (BurdenRate $rate) => $rate->component_name === $component);

        $totalTax = 0;
        foreach ($rates as $rate) {
            if ($rate->percentage !== null) {
                $totalTax += $grossAmount * ($rate->percentage / 100);
            } elseif ($rate->amount !== null) {
                $totalTax += $rate->amount;
            }
        }

        return round($totalTax, 2);
    }

    /**
     * Calculate aggregate payroll data for a pay run.
     *
     * @param  array<PayrollRecord>  $records
     * @return array<string, float>
     */
    public function aggregatePayroll(array $records): array
    {
        $totals = [
            'total_gross' => 0,
            'total_deductions' => 0,
            'total_net' => 0,
        ];

        foreach ($records as $record) {
            $totals['total_gross'] += $record->gross_amount;
            $totals['total_deductions'] += $record->total_deductions;
            $totals['total_net'] += $record->net_amount;
        }

        return array_map(fn ($value) => round($value, 2), $totals);
    }

    /**
     * Validate payroll calculation against expected ranges.
     *
     * @return array<string, bool|string>
     */
    public function validateCalculation(array $calculation): array
    {
        $issues = [];

        // Verify net equals gross minus deductions
        $calculatedNet = $calculation['gross_amount'] - $calculation['total_deductions'];
        if (abs($calculatedNet - $calculation['net_amount']) > 0.01) {
            $issues['net_amount'] = 'Net amount calculation mismatch';
        }

        // Verify deductions don't exceed gross
        if ($calculation['total_deductions'] > $calculation['gross_amount']) {
            $issues['total_deductions'] = 'Total deductions exceed gross amount';
        }

        // Verify net is positive
        if ($calculation['net_amount'] < 0) {
            $issues['net_amount'] = 'Net amount cannot be negative';
        }

        return [
            'valid' => count($issues) === 0,
            'issues' => $issues,
        ];
    }
}
