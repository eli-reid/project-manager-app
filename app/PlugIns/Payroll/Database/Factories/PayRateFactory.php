<?php

namespace App\Domains\Payroll\Database\Factories;

use App\Core\Identity\Models\User;
use App\Domains\Payroll\Models\PayRate;
use App\Domains\Payroll\Models\PayRateType;
use App\Domains\Payroll\Models\PayrollEmployeeProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PayRate>
 */
class PayRateFactory extends Factory
{
    protected $model = PayRate::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'payroll_employee_profile_id' => PayrollEmployeeProfile::factory(),
            'pay_rate_type_id' => PayRateType::factory(),
            'project_id' => null,
            'rate_amount' => fake()->randomFloat(4, 18, 95),
            'effective_date' => fake()->dateTimeBetween('-1 year', 'now'),
            'expiration_date' => null,
            'approved_by' => User::factory(),
        ];
    }
}
