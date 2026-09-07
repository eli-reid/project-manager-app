<?php

namespace App\Domains\Plans\Database\Factories;

use App\Domains\Plans\Models\PlanSheet;
use App\Domains\Projects\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PlanSheet> */
class PlanSheetFactory extends Factory
{
    protected $model = PlanSheet::class;

    public function definition(): array
    {
        return ['project_id' => Project::factory(), 'sheet_number' => fake()->unique()->bothify('A-###'), 'title' => fake()->sentence(3), 'discipline' => 'Architectural'];
    }
}
