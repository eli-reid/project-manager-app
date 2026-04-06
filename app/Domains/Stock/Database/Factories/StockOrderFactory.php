<?php

namespace App\Domains\Stock\Database\Factories;

use App\Core\Identity\Models\User;
use App\Domains\Projects\Models\Project;
use App\Domains\Stock\Models\StockOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockOrder>
 */
class StockOrderFactory extends Factory
{
    protected $model = StockOrder::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'project_id' => null,
            'po_number' => fake()->optional()->bothify('PO-#####'),
            'status' => fake()->randomElement([
                StockOrder::STATUS_PENDING,
                StockOrder::STATUS_APPROVED,
                StockOrder::STATUS_ORDERED,
                StockOrder::STATUS_RECEIVED,
            ]),
            'urgency' => fake()->randomElement([
                StockOrder::URGENCY_LOW,
                StockOrder::URGENCY_MEDIUM,
                StockOrder::URGENCY_HIGH,
            ]),
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function forProject(?Project $project = null): static
    {
        return $this->state(fn () => [
            'project_id' => $project?->id ?? Project::factory(),
        ]);
    }

    public function pending(): static
    {
        return $this->state([
            'status' => StockOrder::STATUS_PENDING,
        ]);
    }
}
