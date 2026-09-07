<?php

declare(strict_types=1);

namespace App\Domains\Plans\Database\Seeders;

use App\Domains\Plans\Models\PlanSet;
use App\Domains\Plans\Models\PlanSheet;
use App\Domains\Plans\Models\PlanSheetRevision;
use App\Domains\Projects\Models\Project;
use Illuminate\Database\Seeder;

final class PlansDemoSeeder extends Seeder
{
    public function run(): void
    {
        $project = Project::query()->first();

        if ($project === null) {
            return;
        }

        $set = PlanSet::factory()->create([
            'project_id' => $project->id,
            'name' => 'Plans Demo Set',
            'status' => PlanSet::STATUS_READY,
            'page_count' => 12,
            'processed_page_count' => 12,
        ]);

        foreach (range(1, 12) as $page) {
            $sheet = PlanSheet::factory()->create([
                'project_id' => $project->id,
                'sheet_number' => 'A-'.str_pad((string) (100 + $page), 3, '0', STR_PAD_LEFT),
                'title' => 'Demo Sheet '.$page,
            ]);
            $revision = PlanSheetRevision::factory()->rendered()->create([
                'plan_sheet_id' => $sheet->id,
                'plan_set_id' => $set->id,
                'page_number' => $page,
                'is_current' => true,
            ]);
            $sheet->update(['current_revision_id' => $revision->id]);
        }
    }
}
