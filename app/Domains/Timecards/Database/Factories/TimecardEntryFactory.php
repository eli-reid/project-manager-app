<?php

namespace App\Domains\Timecards\Database\Factories;

use App\Core\User\Models\User;
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
        ];
    }
}
