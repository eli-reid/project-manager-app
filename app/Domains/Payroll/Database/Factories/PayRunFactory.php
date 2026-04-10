<?php

namespace App\Domains\Payroll\Database\Factories;

use App\Core\Identity\Models\User;
use App\Domains\Payroll\Models\PayrollPeriod;
use App\Domains\Payroll\Models\PayRun;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PayRun>
 */
class PayRunFactory extends Factory
{
    protected $model = PayRun::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'payroll_period_id' => PayrollPeriod::factory(),
            'status' => 'draft',
            'total_gross' => fake()->randomFloat(2, 10000, 50000),
            'total_deductions' => fake()->randomFloat(2, 2000, 10000),
            'total_net' => fake()->randomFloat(2, 5000, 40000),
            'records_count' => fake()->numberBetween(5, 50),
            'created_by' => User::factory(),
        ];
    }
}
