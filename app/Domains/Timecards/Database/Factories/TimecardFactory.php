<?php

namespace App\Domains\Timecards\Database\Factories;

use App\Core\Identity\Models\User;
use App\Domains\Timecards\Models\Timecard;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Timecard>
 */
class TimecardFactory extends Factory
{
    protected $model = Timecard::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $weekStart = now()->copy()->startOfWeek();

        return [
            'user_id' => User::factory(),
            'week_starting' => $weekStart->toDateString(),
            'week_ending' => $weekStart->copy()->addDays(6)->toDateString(),
            'status' => Timecard::STATUS_DRAFT,
            'total_hours' => 0,
            'notes' => fake()->optional()->sentence(),
            'submitted_at' => null,
            'approved_at' => null,
            'approved_by' => null,
            'rejected_at' => null,
            'rejected_by' => null,
            'rejection_reason' => null,
        ];
    }
}
