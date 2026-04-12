<?php

namespace Database\Factories\Domains\Payroll\Models;

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
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'payroll_employee_profile_id' => PayrollEmployeeProfile::factory(),
            'pay_run_id' => null,
        ];
    }
}
