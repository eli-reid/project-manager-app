<?php

namespace App\Domains\Payroll\Database\Factories;

use App\Core\Identity\Models\User;
use App\Domains\Payroll\Models\PayrollPeriod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PayrollPeriod>
 */
class PayrollPeriodFactory extends Factory
{
    protected $model = PayrollPeriod::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('-6 months', 'now');
        $endDate = (clone $startDate)->modify('+6 days');

        return [
            'period_start_date' => $startDate->format('Y-m-d'),
            'period_end_date' => $endDate->format('Y-m-d'),
            'status' => 'open',
            'finalized_at' => null,
            'created_by' => User::factory(),
        ];
    }
}
