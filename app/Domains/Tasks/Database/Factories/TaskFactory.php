<?php

namespace App\Domains\Tasks\Database\Factories;

use App\Core\User\Models\User;
use App\Domains\Projects\Models\Project;
use App\Domains\Tasks\Models\Task;
use App\Domains\Tasks\Models\TaskCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    protected $model = Task::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'task_category_id' => TaskCategory::factory(),
            'parent_task_id' => null,
            'title' => fake()->sentence(4),
            'description' => fake()->optional()->paragraph(),
            'status' => fake()->randomElement(Task::statuses()),
            'priority' => fake()->randomElement(Task::priorities()),
            'estimated_hours' => fake()->randomFloat(2, 1, 16),
            'completion_percentage' => fake()->numberBetween(0, 100),
            'due_date' => fake()->optional()->dateTimeBetween('now', '+30 days'),
            'assigned_to' => User::factory(),
            'is_billable' => true,
            'sort_order' => fake()->numberBetween(0, 100),
        ];
    }
}
