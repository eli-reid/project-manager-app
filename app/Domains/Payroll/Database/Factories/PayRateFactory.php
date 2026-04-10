<?php

namespace App\Domains\Payroll\Database\Factories;

use App\Core\Identity\Models\User;
use App\Domains\Payroll\Models\PayRate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PayRate>
 */
class PayRateFactory extends Factory
{
    protected $model = PayRate::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'rate' => fake()->randomFloat(2, 15, 75), // Hourly rate between $15 and $75
            'effective_date' => now()->subMonths(fake()->numberBetween(0, 12))->toDateString(),
            'end_date' => null,
            'notes' => fake()->optional(0.3)->sentence(),
            'created_by' => User::factory(),
        ];
    }
}
