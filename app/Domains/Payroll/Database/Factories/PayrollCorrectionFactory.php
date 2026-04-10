<?php

namespace App\Domains\Payroll\Database\Factories;

use App\Core\Identity\Models\User;
use App\Domains\Payroll\Models\PayrollCorrection;
use App\Domains\Payroll\Models\PayrollRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PayrollCorrection>
 */
class PayrollCorrectionFactory extends Factory
{
    protected $model = PayrollCorrection::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'payroll_record_id' => PayrollRecord::factory(),
            'type' => fake()->randomElement(['adjustment', 'refund', 'reversal']),
            'status' => 'pending',
            'amount' => fake()->randomFloat(2, -5000, 5000),
            'description' => fake()->sentence(),
            'reason' => fake()->randomElement(['error', 'manual_adjustment', 'policy_change', 'special_payment']),
            'created_by' => User::factory(),
        ];
    }
}
