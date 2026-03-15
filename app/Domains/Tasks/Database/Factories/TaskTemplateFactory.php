<?php

namespace App\Domains\Tasks\Database\Factories;

use App\Core\User\Models\User;
use App\Domains\Tasks\Models\Task;
use App\Domains\Tasks\Models\TaskCategory;
use App\Domains\Tasks\Models\TaskTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TaskTemplate>
 */
class TaskTemplateFactory extends Factory
{
    protected $model = TaskTemplate::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'description' => fake()->optional()->paragraph(),
            'task_category_id' => TaskCategory::factory(),
            'priority' => fake()->randomElement(Task::priorities()),
            'estimated_hours' => fake()->randomFloat(2, 1, 40),
            'is_billable' => true,
            'template_tasks' => [
                [
                    'title' => fake()->sentence(3),
                    'priority' => Task::PRIORITY_MEDIUM,
                    'estimated_hours' => fake()->randomFloat(2, 1, 8),
                ],
            ],
            'is_active' => true,
            'created_by' => User::factory(),
        ];
    }
}
