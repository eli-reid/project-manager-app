<?php

namespace App\Domains\Projects\Database\Factories;

use App\Core\Identity\Models\User;
use App\Domains\Projects\Enums\ProjectStatusEnum;
use App\Domains\Projects\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    protected $model = Project::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $statusOptions = array_keys(ProjectStatusEnum::toArray());

        return [
            'name' => fake()->company().' Project',
            'project_number' => fake()->unique()->bothify('PRJ-####'),
            'accounting_code' => fake()->optional()->bothify('ACCT-###'),
            'description' => fake()->optional()->sentence(),
            'status' => fake()->randomElement($statusOptions),
            'start_date' => fake()->optional()->dateTimeBetween('-30 days', 'now'),
            'end_date' => fake()->optional()->dateTimeBetween('now', '+120 days'),
            'project_manager_id' => User::factory(),
            'leave_category' => null,
            'is_active' => true,
        ];
    }
}
