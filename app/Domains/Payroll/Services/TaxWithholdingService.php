<?php

namespace App\Domains\Payroll\Services;

class TaxWithholdingService
{
    /**
     * @return array{federal:float,state:float,local:float,social_security:float,medicare:float,total:float}
     */
    public function calculate(float $taxableGross): array
    {
        $taxableGross = max(0.0, $taxableGross);

        $federal = round($taxableGross * 0.12, 2);
        $state = round($taxableGross * 0.05, 2);
        $local = round($taxableGross * 0.01, 2);
        $socialSecurity = round($taxableGross * 0.062, 2);
        $medicare = round($taxableGross * 0.0145, 2);

        return [
            'federal' => $federal,
            'state' => $state,
            'local' => $local,
            'social_security' => $socialSecurity,
            'medicare' => $medicare,
            'total' => round($federal + $state + $local + $socialSecurity + $medicare, 2),
        ];
    }
}
