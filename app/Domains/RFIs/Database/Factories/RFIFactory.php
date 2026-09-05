<?php

namespace App\Domains\RFIs\Database\Factories;

use App\Core\Identity\Models\User;
use App\Domains\Projects\Models\Project;
use App\Domains\RFIs\Models\RFI;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RFI>
 */
class RFIFactory extends Factory
{
    protected $model = RFI::class;

    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'number' => $this->faker->unique()->numberBetween(1, 999),
            'subject' => $this->faker->sentence(6),
            'body' => $this->faker->paragraph(),
            'status' => RFI::STATUS_DRAFT,
            'requested_by_id' => User::factory(),
            'answered_by_id' => null,
            'answer' => null,
            'due_date' => null,
            'answered_at' => null,
            'cost_impact' => null,
            'schedule_impact_days' => null,
        ];
    }

    public function submitted(): static
    {
        return $this->state(['status' => RFI::STATUS_SUBMITTED]);
    }

    public function answered(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => RFI::STATUS_ANSWERED,
            'answered_by_id' => User::factory(),
            'answer' => $this->faker->paragraph(),
            'answered_at' => now(),
        ]);
    }

    public function closed(): static
    {
        return $this->state(['status' => RFI::STATUS_CLOSED]);
    }

    public function cancelled(): static
    {
        return $this->state(['status' => RFI::STATUS_CANCELLED]);
    }
}
