<?php

namespace App\Domains\Payroll\Database\Factories;

use App\Domains\Payroll\Models\Deduction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Deduction>
 */
class DeductionFactory extends Factory
{
    protected $model = Deduction::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'category' => fake()->randomElement(['tax', 'garnishment', 'voluntary', 'union', 'benefit']),
            'calculation_method' => fake()->randomElement(['flat', 'percentage', 'hourly', 'per_period']),
            'amount' => fake()->randomFloat(4, 1, 25),
            'priority' => fake()->numberBetween(1, 20),
            'pre_tax' => fake()->boolean(),
            'max_annual' => fake()->optional()->randomFloat(2, 100, 5000),
        ];
    }
}
