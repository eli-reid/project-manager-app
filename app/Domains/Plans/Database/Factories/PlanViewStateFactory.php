<?php

namespace App\Domains\Plans\Database\Factories;

use App\Core\Identity\Models\User;
use App\Domains\Plans\Models\PlanSheet;
use App\Domains\Plans\Models\PlanViewState;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PlanViewState> */
class PlanViewStateFactory extends Factory
{
    protected $model = PlanViewState::class;

    public function definition(): array
    {
        return ['user_id' => User::factory(), 'plan_sheet_id' => PlanSheet::factory(), 'zoom' => 1, 'center_x' => 0.5, 'center_y' => 0.5, 'last_viewed_at' => now()];
    }
}
