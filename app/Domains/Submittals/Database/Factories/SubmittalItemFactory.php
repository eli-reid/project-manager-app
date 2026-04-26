<?php

namespace App\Domains\Submittals\Database\Factories;

use App\Domains\Submittals\Models\Submittal;
use App\Domains\Submittals\Models\SubmittalItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SubmittalItem>
 */
class SubmittalItemFactory extends Factory
{
    protected $model = SubmittalItem::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'submittal_id' => Submittal::factory(),
            'description' => fake()->sentence(4),
            'manufacturer' => fake()->optional()->company(),
            'model' => fake()->optional()->bothify('MDL-###'),
            'part_number' => fake()->optional()->bothify('PN-####'),
            'quantity' => fake()->randomFloat(2, 1, 25),
            'unit' => fake()->randomElement(['ea', 'set', 'pkg']),
            'status' => SubmittalItem::STATUS_PENDING,
            'comments' => null,
        ];
    }
}
