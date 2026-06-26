<?php

namespace App\Domains\Payroll\Database\Factories;

use App\Core\Identity\Models\User;
use App\Domains\Payroll\Enums\PayRunStatus;
use App\Domains\Payroll\Models\PayRun;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PayRun>
 */
class PayRunFactory extends Factory
{
    protected $model = PayRun::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $periodStart = fake()->dateTimeBetween('-3 months', 'now');
        $periodEnd = (clone $periodStart)->modify('+6 days');

        return [
            'pay_period_start' => $periodStart,
            'pay_period_end' => $periodEnd,
            'pay_date' => (clone $periodEnd)->modify('+7 days'),
            'status' => fake()->randomElement([PayRunStatus::Draft, PayRunStatus::Preview, PayRunStatus::Approved]),
            'total_gross' => fake()->randomFloat(2, 1000, 25000),
            'total_net' => fake()->randomFloat(2, 800, 18000),
            'total_taxes' => fake()->randomFloat(2, 150, 7000),
            'employee_count' => fake()->numberBetween(1, 40),
            'created_by' => User::factory(),
            'approved_by' => null,
            'finalized_at' => null,
        ];
    }
}
