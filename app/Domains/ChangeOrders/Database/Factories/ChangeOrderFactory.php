<?php

namespace App\Domains\ChangeOrders\Database\Factories;

use App\Core\Identity\Models\User;
use App\Domains\ChangeOrders\Models\ChangeOrder;
use App\Domains\Projects\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChangeOrder>
 */
class ChangeOrderFactory extends Factory
{
    protected $model = ChangeOrder::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $laborAmount = fake()->randomFloat(2, 250, 4000);
        $materialsAmount = fake()->randomFloat(2, 100, 3000);

        return [
            'project_id' => Project::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->optional()->paragraph(),
            'status' => ChangeOrder::STATUS_DRAFT,
            'labor_amount' => $laborAmount,
            'materials_amount' => $materialsAmount,
            'total_amount' => round($laborAmount + $materialsAmount, 2),
            'requested_by_id' => User::factory(),
            'approved_by_id' => null,
            'rejected_by_id' => null,
            'submitted_at' => null,
            'approved_at' => null,
            'rejected_at' => null,
            'implemented_at' => null,
            'cancelled_at' => null,
            'client_approved_at' => null,
            'client_approval_reference' => null,
            'rejection_reason' => null,
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
