<?php

namespace App\Domains\Plans\Database\Factories;

use App\Domains\Plans\Models\PlanAnnotation;
use App\Domains\Plans\Models\PlanAnnotationComment;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PlanAnnotationComment> */
class PlanAnnotationCommentFactory extends Factory
{
    protected $model = PlanAnnotationComment::class;

    public function definition(): array
    {
        return ['plan_annotation_id' => PlanAnnotation::factory(), 'body' => fake()->sentence()];
    }
}
