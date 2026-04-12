<?php

namespace App\Domains\Payroll\Services;

class GrossToNetService
{
    /**
     * @param  array{federal:float,state:float,local:float,social_security:float,medicare:float,total:float}  $taxes
     * @return array{taxable_gross:float,total_taxes:float,net_pay:float}
     */
    public function calculate(float $grossPay, float $preTaxDeductions, float $postTaxDeductions, array $taxes): array
    {
        $grossPay = round(max(0.0, $grossPay), 2);
        $preTaxDeductions = round(max(0.0, $preTaxDeductions), 2);
        $postTaxDeductions = round(max(0.0, $postTaxDeductions), 2);
        $taxableGross = round(max(0.0, $grossPay - $preTaxDeductions), 2);
        $totalTaxes = round(max(0.0, (float) ($taxes['total'] ?? 0.0)), 2);
        $netPay = round(max(0.0, $grossPay - $preTaxDeductions - $totalTaxes - $postTaxDeductions), 2);

        return [
            'taxable_gross' => $taxableGross,
            'total_taxes' => $totalTaxes,
            'net_pay' => $netPay,
        ];
    }
}
