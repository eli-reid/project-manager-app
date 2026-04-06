<?php

namespace App\Domains\Dailies\Database\Factories;

use App\Core\Identity\Models\User;
use App\Domains\Dailies\Models\DailyReport;
use App\Domains\Projects\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DailyReport>
 */
class DailyReportFactory extends Factory
{
    protected $model = DailyReport::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'custom_project_name' => null,
            'user_id' => User::factory(),
            'submitted_by_id' => null,
            'report_date' => now()->toDateString(),
            'status' => DailyReport::STATUS_DRAFT,
            'work_performed' => [],
            'materials_used' => [],
            'equipment_used' => [],
            'safety_issues' => [],
            'delays' => [],
            'visitors' => [],
            'onsite_employees' => [],
            'weather_condition' => fake()->optional()->word(),
            'temperature' => fake()->optional()->randomFloat(2, 20, 95),
            'temperature_unit' => 'F',
            'total_regular_hours' => 0,
            'total_overtime_hours' => 0,
            'total_hours' => 0,
            'additional_notes' => fake()->optional()->sentence(),
            'rejection_reason' => null,
        ];
    }
}
