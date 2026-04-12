<?php

namespace App\Domains\Payroll\Services;

use App\Domains\Payroll\Models\PayRate;
use App\Domains\Payroll\Models\PayRateType;
use App\Domains\Payroll\Models\PayrollEmployeeProfile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class PayrollRateResolutionService
{
    /**
     * Resolve the best applicable pay rate for an employee on a given work date.
     *
     * Precedence: project-specific rate > global (null project_id) rate.
     * The rate must be effective on the work date and not yet expired.
     */
    public function resolve(
        PayrollEmployeeProfile $profile,
        string $rateTypeKey,
        ?string $projectId,
        Carbon $workDate,
    ): ?PayRate {
        $type = PayRateType::query()->where('key', $rateTypeKey)->first();

        if ($type === null) {
            return null;
        }

        $workDateStr = $workDate->toDateString();

        $baseQuery = fn () => PayRate::query()
            ->where('payroll_employee_profile_id', $profile->id)
            ->where('pay_rate_type_id', $type->id)
            ->where('effective_date', '<=', $workDateStr)
            ->where(function ($q) use ($workDateStr): void {
                $q->whereNull('expiration_date')
                    ->orWhere('expiration_date', '>=', $workDateStr);
            });

        // Try project-specific rate first.
        if ($projectId !== null) {
            $projectRate = $baseQuery()
                ->where('project_id', $projectId)
                ->latest('effective_date')
                ->first();

            if ($projectRate !== null) {
                return $projectRate;
            }
        }

        // Fall back to the global rate (no project constraint).
        return $baseQuery()
            ->whereNull('project_id')
            ->latest('effective_date')
            ->first();
    }

    /**
     * Resolve all active rate types for an employee on a work date.
     *
     * @return Collection<string, PayRate>
     */
    public function resolveAll(
        PayrollEmployeeProfile $profile,
        ?string $projectId,
        Carbon $workDate,
    ): Collection {
        return PayRateType::query()
            ->where('is_active', true)
            ->get()
            ->mapWithKeys(fn (PayRateType $type): array => [
                $type->key => $this->resolve($profile, $type->key, $projectId, $workDate),
            ])
            ->filter()
            ->values()
            ->mapWithKeys(fn (PayRate $rate): array => [
                $rate->payRateType->key => $rate,
            ]);
    }

    /**
     * Returns true when the employee has at least a 'standard' rate applicable on the date.
     */
    public function hasAnyRate(
        PayrollEmployeeProfile $profile,
        ?string $projectId,
        Carbon $workDate,
    ): bool {
        return $this->resolve($profile, 'standard', $projectId, $workDate) !== null;
    }
}
