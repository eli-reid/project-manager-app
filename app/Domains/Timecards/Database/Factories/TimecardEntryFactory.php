<?php

namespace App\Domains\Timecards\Database\Factories;

use App\Core\Identity\Models\User;
use App\Domains\Projects\Models\Project;
use App\Domains\Timecards\Models\Timecard;
use App\Domains\Timecards\Models\TimecardEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TimecardEntry>
 */
class TimecardEntryFactory extends Factory
{
    protected $model = TimecardEntry::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'timecard_id' => Timecard::factory(),
            'user_id' => User::factory(),
            'project_id' => Project::factory(),
            'custom_project_name' => null,
            'date' => now()->toDateString(),
            'start_time' => '07:00:00',
            'hours' => fake()->randomFloat(2, 0.5, 12),
            'notes' => fake()->optional()->sentence(),
            'cost_code_id' => null,
            'regular_hours' => null,
            'overtime_hours' => null,
            'double_time_hours' => null,
            'work_classification' => null,
            'prevailing_base_rate' => null,
            'prevailing_fringe_rate' => null,
            'fringe_payment_method' => null,
        ];
    }

    public function withPrevailingWage(string $classification = 'Journeyman Electrician'): static
    {
        return $this->state([
            'work_classification' => $classification,
            'prevailing_base_rate' => fake()->randomFloat(4, 30, 80),
            'prevailing_fringe_rate' => fake()->randomFloat(4, 10, 25),
            'fringe_payment_method' => fake()->randomElement(['cash', 'plan']),
        ]);
    }
}
