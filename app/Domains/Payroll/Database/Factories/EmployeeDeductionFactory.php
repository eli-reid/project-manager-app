<?php

namespace App\Domains\Payroll\Database\Factories;

use App\Domains\Payroll\Models\Deduction;
use App\Domains\Payroll\Models\EmployeeDeduction;
use App\Domains\Payroll\Models\PayrollEmployeeProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmployeeDeduction>
 */
class EmployeeDeductionFactory extends Factory
{
    protected $model = EmployeeDeduction::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'payroll_employee_profile_id' => PayrollEmployeeProfile::factory(),
            'deduction_id' => Deduction::factory(),
            'override_amount' => fake()->optional()->randomFloat(4, 1, 35),
            'effective_date' => fake()->dateTimeBetween('-1 year', 'now'),
            'end_date' => null,
            'status' => 'active',
        ];
    }
}
