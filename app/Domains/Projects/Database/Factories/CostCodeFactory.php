<?php

namespace App\Domains\Projects\Database\Factories;

use App\Domains\Projects\Models\CostCode;
use App\Domains\Projects\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CostCode>
 */
class CostCodeFactory extends Factory
{
    protected $model = CostCode::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'code' => fake()->unique()->numerify('#####'),
            'description' => fake()->sentence(3),
            'budget_hours' => fake()->optional()->randomFloat(2, 10, 500),
            'budget_cost' => fake()->optional()->randomFloat(2, 500, 50000),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
