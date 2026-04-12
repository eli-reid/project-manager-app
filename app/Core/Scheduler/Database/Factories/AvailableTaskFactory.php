<?php

namespace App\Core\Scheduler\Database\Factories;

use App\Core\Scheduler\Models\AvailableTask;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AvailableTask>
 */
class AvailableTaskFactory extends Factory
{
    protected $model = AvailableTask::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'feature_type' => fake()->unique()->slug(2, '_'),
            'name' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'task_config' => [],
            'is_active' => true,
        ];
    }
}
