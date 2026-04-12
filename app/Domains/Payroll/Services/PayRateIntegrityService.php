<?php

namespace App\Domains\Payroll\Services;

use App\Domains\Payroll\Models\PayRate;
use DomainException;

class PayRateIntegrityService
{
    /**
     * Assert no conflicting active rate exists for the given rate's
     * employee + type + project scope combination.
     *
     * Active means expiration_date IS NULL. Because project_id is nullable,
     * the NULL case (default rate) and a project-specific rate are treated as
     * separate scopes and compared independently.
     *
     * @throws DomainException
     */
    public function assertNoActiveConflict(PayRate $rate): void
    {
        $query = PayRate::query()
            ->where('payroll_employee_profile_id', $rate->payroll_employee_profile_id)
            ->where('pay_rate_type_id', $rate->pay_rate_type_id)
            ->active();

        if ($rate->project_id === null) {
            $query->whereNull('project_id');
        } else {
            $query->where('project_id', $rate->project_id);
        }

        if ($rate->exists) {
            $query->where('id', '!=', $rate->id);
        }

        if ($query->exists()) {
            throw new DomainException(
                'An active rate already exists for this employee, type, and project scope. '.
                'Expire the existing rate before creating a new one.'
            );
        }
    }
}
