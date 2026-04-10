<?php

namespace App\Domains\Payroll\Database\Factories;

use App\Core\Identity\Models\User;
use App\Domains\Payroll\Models\PayrollRecord;
use App\Domains\Payroll\Models\PayRun;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PayrollRecord>
 */
class PayrollRecordFactory extends Factory
{
    protected $model = PayrollRecord::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $regularHours = fake()->randomFloat(2, 35, 40);
        $overtimeHours = fake()->randomFloat(2, 0, 10);
        $hourlyRate = fake()->randomFloat(2, 15, 75);
        $grossAmount = ($regularHours * $hourlyRate) + ($overtimeHours * $hourlyRate * 1.5);
        $totalDeductions = fake()->randomFloat(2, 0, $grossAmount * 0.4);

        return [
            'pay_run_id' => PayRun::factory(),
            'user_id' => User::factory(),
            'regular_hours' => $regularHours,
            'overtime_hours' => $overtimeHours,
            'gross_amount' => $grossAmount,
            'federal_tax' => fake()->randomFloat(2, 0, $grossAmount * 0.22),
            'state_tax' => fake()->randomFloat(2, 0, $grossAmount * 0.1),
            'local_tax' => fake()->randomFloat(2, 0, $grossAmount * 0.05),
            'social_security' => round($grossAmount * 0.062, 2),
            'medicare' => round($grossAmount * 0.0145, 2),
            'total_deductions' => $totalDeductions,
            'net_amount' => $grossAmount - $totalDeductions,
            'created_by' => User::factory(),
        ];
    }
}
