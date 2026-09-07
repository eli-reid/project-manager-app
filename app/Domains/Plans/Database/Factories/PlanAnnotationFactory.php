<?php

namespace App\Domains\Plans\Database\Factories;

use App\Domains\Plans\Models\PlanAnnotation;
use App\Domains\Plans\Models\PlanSheet;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PlanAnnotation> */
class PlanAnnotationFactory extends Factory
{
    protected $model = PlanAnnotation::class;

    public function definition(): array
    {
        return ['plan_sheet_id' => PlanSheet::factory(), 'type' => 'rect', 'geometry' => ['x' => 0.1, 'y' => 0.1, 'w' => 0.2, 'h' => 0.2], 'style' => ['color' => '#ff0000', 'width' => 2]];
    }
}
