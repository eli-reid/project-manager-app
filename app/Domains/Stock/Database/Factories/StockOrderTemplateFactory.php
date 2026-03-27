<?php

namespace App\Domains\Stock\Database\Factories;

use App\Core\User\Models\User;
use App\Domains\Stock\Models\StockOrderTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockOrderTemplate>
 */
class StockOrderTemplateFactory extends Factory
{
    protected $model = StockOrderTemplate::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'description' => fake()->optional()->sentence(),
            'urgency' => fake()->randomElement([
                StockOrderTemplate::URGENCY_LOW,
                StockOrderTemplate::URGENCY_MEDIUM,
                StockOrderTemplate::URGENCY_HIGH,
            ]),
            'notes' => fake()->optional()->sentence(),
            'template_items' => [
                [
                    'item_name' => fake()->words(2, true),
                    'quantity' => fake()->numberBetween(1, 20),
                ],
                [
                    'item_name' => fake()->words(2, true),
                    'quantity' => fake()->numberBetween(1, 20),
                ],
            ],
            'is_active' => true,
            'is_global' => false,
            'created_by' => User::factory(),
        ];
    }

    public function globalTemplate(): static
    {
        return $this->state([
            'is_global' => true,
        ]);
    }
}
