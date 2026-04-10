<?php

namespace App\Domains\Payroll\Database\Factories;

use App\Core\Identity\Models\User;
use App\Domains\Payroll\Models\BurdenRate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BurdenRate>
 */
class BurdenRateFactory extends Factory
{
    protected $model = BurdenRate::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => null, // Default to global scope
            'scope' => 'global',
            'component_name' => fake()->randomElement(['federal_tax', 'state_tax', 'medicare', 'social_security']),
            'percentage' => fake()->randomFloat(4, 0.1, 15), // Percentage between 0.1% and 15%
            'amount' => null,
            'effective_date' => now()->subMonths(fake()->numberBetween(0, 12))->toDateString(),
            'end_date' => null,
            'description' => fake()->sentence(),
            'created_by' => User::factory(),
        ];
    }
}
