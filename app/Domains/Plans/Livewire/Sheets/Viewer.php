<?php

declare(strict_types=1);

namespace App\Domains\Plans\Livewire\Sheets;

use App\Domains\Plans\Models\PlanSheet;
use App\Domains\Plans\Models\PlanSheetRevision;
use App\Domains\Plans\Models\PlanViewState;
use App\Domains\Plans\Services\PlanRevisionService;
use App\Domains\Projects\Models\Project;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Viewer extends Component
{
    use AuthorizesRequests;

    public Project $project;

    public PlanSheet $sheet;

    public string $activeRevisionId = '';

    public float $zoom = 1.0;

    public float $centerX = 0.5;

    public float $centerY = 0.5;

    public function mount(Project $project, PlanSheet $sheet): void
    {
        abort_unless($sheet->project_id === $project->id, 404);
        $this->project = $project;
        $this->sheet = $sheet->load(['currentRevision', 'revisions.set']);
        $this->authorize('view', $sheet);

        $currentRevision = $this->sheet->currentRevision ?: $this->sheet->revisions->sortByDesc('page_number')->first();
        abort_unless($currentRevision instanceof PlanSheetRevision, 404);
        $this->activeRevisionId = $currentRevision->id;

        $state = PlanViewState::query()
            ->where('user_id', Auth::id())
            ->where('plan_sheet_id', $sheet->id)
            ->first();

        if ($state instanceof PlanViewState) {
            $this->zoom = (float) $state->zoom;
            $this->centerX = (float) $state->center_x;
            $this->centerY = (float) $state->center_y;
        }
    }

    public function selectRevision(string $revisionId): void
    {
        abort_unless($this->sheet->revisions->contains('id', $revisionId), 404);
        $this->activeRevisionId = $revisionId;
    }

    public function publishRevision(string $revisionId, PlanRevisionService $revisions): void
    {
        $this->authorize('publishRevision', $this->sheet);
        $revision = $this->sheet->revisions->firstWhere('id', $revisionId);
        abort_unless($revision instanceof PlanSheetRevision, 404);
        $revisions->publish($this->sheet, $revision);
        $this->sheet->refresh()->load(['currentRevision', 'revisions.set']);
    }

    public function persistViewState(float $zoom, float $centerX, float $centerY): void
    {
        $this->zoom = max(0.25, min(4, $zoom));
        $this->centerX = max(0, min(1, $centerX));
        $this->centerY = max(0, min(1, $centerY));

        PlanViewState::query()->updateOrCreate(
            ['user_id' => Auth::id(), 'plan_sheet_id' => $this->sheet->id],
            ['zoom' => $this->zoom, 'center_x' => $this->centerX, 'center_y' => $this->centerY, 'last_viewed_at' => now()],
        );
    }

    public function render()
    {
        $revision = $this->sheet->revisions->firstWhere('id', $this->activeRevisionId);
        abort_unless($revision instanceof PlanSheetRevision, 404);

        return view('plans::livewire.sheets.viewer', [
            'revision' => $revision,
            'siblings' => PlanSheet::query()
                ->where('project_id', $this->project->id)
                ->select(['id', 'sheet_number', 'title', 'sort_index'])
                ->orderBy('sort_index')
                ->limit(240)
                ->get(),
        ]);
    }
}
