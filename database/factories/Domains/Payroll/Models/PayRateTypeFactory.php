<?php

namespace Database\Factories\Domains\Payroll\Models;

use App\Domains\Payroll\Models\PayRateType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PayRateType>
 */
class PayRateTypeFactory extends Factory
{
    protected $model = PayRateType::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'key' => 'rate-'.fake()->unique()->slug(2),
            'name' => fake()->words(2, true),
            'description' => fake()->optional()->sentence(),
            'is_active' => true,
            'is_system' => false,
            'sort_order' => fake()->numberBetween(0, 50),
        ];
    }

    public function standard(): static
    {
        return $this->state(fn (): array => [
            'key' => 'standard',
            'name' => 'Standard',
            'description' => 'Default base hourly rate.',
            'is_system' => true,
            'sort_order' => 10,
        ]);
    }
}
