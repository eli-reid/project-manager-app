<?php

namespace App\Domains\Payroll\Database\Factories;

use App\Domains\Payroll\Models\PayRateType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PayRateType>
 */
class PayRateTypeFactory extends Factory
{
    protected $model = PayRateType::class;

    /**
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
            'is_active' => true,
            'is_system' => true,
            'sort_order' => 10,
        ]);
    }

    public function prevailingBase(): static
    {
        return $this->state(fn (): array => [
            'key' => 'prevailing_base',
            'name' => 'Prevailing Wage Base',
            'description' => 'Base hourly rate for prevailing wage projects (Davis-Bacon / SCA).',
            'is_active' => true,
            'is_system' => true,
            'sort_order' => 20,
        ]);
    }

    public function prevailingFringe(): static
    {
        return $this->state(fn (): array => [
            'key' => 'prevailing_fringe',
            'name' => 'Prevailing Wage Fringe',
            'description' => 'Fringe benefit rate for prevailing wage projects.',
            'is_active' => true,
            'is_system' => true,
            'sort_order' => 30,
        ]);
    }
}
