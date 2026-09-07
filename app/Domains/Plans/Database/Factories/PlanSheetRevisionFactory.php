<?php

namespace App\Domains\Plans\Database\Factories;

use App\Domains\Plans\Models\PlanSet;
use App\Domains\Plans\Models\PlanSheet;
use App\Domains\Plans\Models\PlanSheetRevision;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PlanSheetRevision> */
class PlanSheetRevisionFactory extends Factory
{
    protected $model = PlanSheetRevision::class;

    public function definition(): array
    {
        return ['plan_sheet_id' => PlanSheet::factory(), 'plan_set_id' => PlanSet::factory(), 'page_number' => fake()->numberBetween(1, 50), 'status' => 'pending'];
    }

    public function rendered(): static
    {
        return $this->state(fn (): array => ['status' => 'rendered', 'thumbnail_path' => 'plans/thumb.webp', 'preview_path' => 'plans/preview.webp', 'width' => 2000, 'height' => 1400]);
    }
}
