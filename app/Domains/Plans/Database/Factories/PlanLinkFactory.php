<?php

namespace App\Domains\Plans\Database\Factories;

use App\Domains\Plans\Models\PlanLink;
use App\Domains\Plans\Models\PlanSheet;
use App\Domains\Plans\Models\PlanSheetRevision;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PlanLink> */
class PlanLinkFactory extends Factory
{
    protected $model = PlanLink::class;

    public function definition(): array
    {
        return ['from_revision_id' => PlanSheetRevision::factory(), 'target_sheet_id' => PlanSheet::factory(), 'hotspot' => ['x' => 0.1, 'y' => 0.1, 'w' => 0.2, 'h' => 0.2], 'label' => fake()->words(2, true)];
    }
}
