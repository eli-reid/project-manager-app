<?php

namespace App\Domains\Payroll\Observers;

use App\Domains\Payroll\Models\PayRate;
use App\Domains\Payroll\Services\PayRateIntegrityService;

class PayRateObserver
{
    public function __construct(private readonly PayRateIntegrityService $integrityService) {}

    public function creating(PayRate $rate): void
    {
        $this->integrityService->assertNoActiveConflict($rate);
    }

    public function updating(PayRate $rate): void
    {
        if ($rate->isDirty(['payroll_employee_profile_id', 'pay_rate_type_id', 'project_id', 'expiration_date'])) {
            $this->integrityService->assertNoActiveConflict($rate);
        }
    }
}
