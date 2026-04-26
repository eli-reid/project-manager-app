<?php

namespace App\Domains\Submittals\Database\Factories;

use App\Core\Identity\Models\User;
use App\Domains\Projects\Models\Project;
use App\Domains\Submittals\Models\Submittal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Submittal>
 */
class SubmittalFactory extends Factory
{
    protected $model = Submittal::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'type' => fake()->randomElement(['lighting_fixture', 'gear_package', 'material_data', 'shop_drawing']),
            'spec_reference' => fake()->optional()->bothify('## ## ##'),
            'vendor' => fake()->optional()->company(),
            'need_by_date' => fake()->optional()->dateTimeBetween('now', '+60 days'),
            'status' => Submittal::STATUS_DRAFT,
            'submitted_by_id' => User::factory(),
            'current_reviewer_id' => null,
            'rejection_reason' => null,
            'submitted_at' => null,
            'approved_at' => null,
            'rejected_at' => null,
            'cancelled_at' => null,
            'distributed_at' => null,
        ];
    }

    public function underReview(): static
    {
        return $this->state([
            'status' => Submittal::STATUS_UNDER_REVIEW,
            'submitted_at' => now(),
        ]);
    }
}
