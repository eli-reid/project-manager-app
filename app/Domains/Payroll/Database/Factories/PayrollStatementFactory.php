<?php

namespace App\Domains\Payroll\Database\Factories;

use App\Core\Identity\Models\User;
use App\Domains\Payroll\Models\PayrollEmployeeProfile;
use App\Domains\Payroll\Models\PayrollStatement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PayrollStatement>
 */
class PayrollStatementFactory extends Factory
{
    protected $model = PayrollStatement::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'payroll_employee_profile_id' => PayrollEmployeeProfile::factory(),
            'pay_run_id' => null,
            'total_regular_hours' => fake()->randomFloat(2, 0, 40),
            'total_ot_hours' => fake()->randomFloat(2, 0, 12),
            'total_dt_hours' => fake()->randomFloat(2, 0, 4),
            'gross_pay' => fake()->randomFloat(2, 600, 2800),
            'federal_tax' => fake()->randomFloat(2, 50, 500),
            'state_tax' => fake()->randomFloat(2, 0, 180),
            'local_tax' => fake()->randomFloat(2, 0, 60),
            'social_security' => fake()->randomFloat(2, 30, 180),
            'medicare' => fake()->randomFloat(2, 10, 50),
            'other_deductions' => fake()->randomFloat(2, 0, 300),
            'net_pay' => fake()->randomFloat(2, 450, 2200),
            'ytd_gross' => fake()->randomFloat(2, 1000, 25000),
            'ytd_federal_tax' => fake()->randomFloat(2, 120, 5000),
            'ytd_net' => fake()->randomFloat(2, 800, 18000),
        ];
    }
}
