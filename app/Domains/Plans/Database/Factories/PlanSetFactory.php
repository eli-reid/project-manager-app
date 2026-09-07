<?php

namespace App\Domains\Plans\Database\Factories;

use App\Domains\Plans\Models\PlanSet;
use App\Domains\Projects\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PlanSet> */
class PlanSetFactory extends Factory
{
    protected $model = PlanSet::class;

    public function definition(): array
    {
        return ['project_id' => Project::factory(), 'name' => fake()->words(3, true), 'discipline' => fake()->randomElement(['Architectural', 'Structural', 'MEP']), 'status' => PlanSet::STATUS_PENDING];
    }
}
