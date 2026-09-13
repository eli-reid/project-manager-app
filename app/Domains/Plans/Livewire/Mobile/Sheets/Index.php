<?php

declare(strict_types=1);

namespace App\Domains\Plans\Livewire\Mobile\Sheets;

use App\Domains\Plans\Models\PlanSheet;
use App\Domains\Projects\Models\Project;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
final class Index extends Component
{
    use AuthorizesRequests;

    public Project $project;

    public function mount(Project $project): void
    {
        $this->project = $project;
        $this->authorize('view', $project);
    }

    public function render()
    {
        return view('plans::livewire.mobile.sheets.index', [
            'sheets' => PlanSheet::query()
                ->whereBelongsTo($this->project)
                ->with('currentRevision:id,plan_sheet_id,thumbnail_path')
                ->orderBy('sort_index')
                ->limit(100)
                ->get(['id', 'project_id', 'sheet_number', 'title', 'current_revision_id']),
        ]);
    }
}
