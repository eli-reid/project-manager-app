<?php

namespace App\Core\Scheduler\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Core\Scheduler\Models\AvailableTask>
 */
class AvailableTaskFactory extends Factory
{
    protected $model = \App\Core\Scheduler\Models\AvailableTask::class;

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
