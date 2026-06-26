<?php

namespace App\Domains\Submittals\Database\Factories;

use App\Core\Identity\Models\User;
use App\Domains\Submittals\Models\Submittal;
use App\Domains\Submittals\Models\SubmittalApproval;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SubmittalApproval>
 */
class SubmittalApprovalFactory extends Factory
{
    protected $model = SubmittalApproval::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'submittal_id' => Submittal::factory(),
            'step' => 1,
            'reviewer_id' => User::factory(),
            'status' => SubmittalApproval::STATUS_PENDING,
            'reviewed_at' => null,
            'comments' => null,
        ];
    }
}
