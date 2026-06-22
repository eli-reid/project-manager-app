<?php

namespace App\Domains\Stock\Database\Factories;

use App\Domains\Stock\Models\StockOrder;
use App\Domains\Stock\Models\StockOrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockOrderItem>
 */
class StockOrderItemFactory extends Factory
{
    protected $model = StockOrderItem::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'stock_order_id' => StockOrder::factory(),
            'quantity' => fake()->numberBetween(1, 50),
            'item_name' => fake()->words(3, true),
            'status' => fake()->randomElement(['pending', 'received', 'cancelled']),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
