<?php

namespace App\Domains\Payroll\Database\Factories;

use App\Core\Identity\Models\User;
use App\Domains\Payroll\Models\WeeklyEmployeeHoursAdjustment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WeeklyEmployeeHoursAdjustment>
 */
class WeeklyEmployeeHoursAdjustmentFactory extends Factory
{
    protected $model = WeeklyEmployeeHoursAdjustment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'week_start' => now()->startOfWeek()->toDateString(),
            'user_id' => User::factory(),
            'source_hours' => 40.0,
            'adjusted_hours' => 40.0,
            'reason' => 'Payroll correction',
            'edited_by_id' => User::factory(),
            'edited_at' => now(),
            'metadata' => null,
        ];
    }
}
