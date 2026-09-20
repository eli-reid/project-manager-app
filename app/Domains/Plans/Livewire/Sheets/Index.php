<?php

declare(strict_types=1);

namespace App\Domains\Plans\Livewire\Sheets;

use App\Core\Identity\Models\User;
use App\Domains\Plans\Models\PlanSet;
use App\Domains\Plans\Models\PlanSheet;
use App\Domains\Projects\Models\Project;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

final class Index extends Component
{
    use AuthorizesRequests;

    public Project $project;

    public string $search = '';

    public string $discipline = '';

    public string $setId = '';

    public string $mode = 'grid';

    public int $perPage = 24;

    public bool $canDeletePlans = false;

    public function mount(Project $project): void
    {
        $this->project = $project;
        $this->authorize('view', $project);
        $this->authorize('viewAny', PlanSet::class);
        $user = Auth::user();
        $this->canDeletePlans = $user instanceof User && $user->hasPermission('plans.delete');
    }

    public function deleteSheet(string $sheetId): void
    {
        $sheet = PlanSheet::query()->whereBelongsTo($this->project)->findOrFail($sheetId);
        $this->authorize('delete', $sheet);

        $sheet->revisions()->delete();
        $sheet->delete();

        session()->flash('success', 'Plan sheet deleted successfully.');
    }

    public function loadMore(): void
    {
        $this->perPage = min($this->perPage + 24, 240);
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'discipline', 'setId']);
    }

    public function render()
    {
        $sheets = PlanSheet::query()
            ->whereBelongsTo($this->project)
            ->select(['id', 'project_id', 'sheet_number', 'title', 'discipline', 'sort_index', 'current_revision_id'])
            ->with(['currentRevision:id,plan_sheet_id,revision_label,page_number,thumbnail_path,preview_path,is_current'])
            ->withCount('annotations')
            ->when($this->search !== '', fn ($query) => $query->where(fn ($query) => $query
                ->where('sheet_number', 'like', '%'.$this->search.'%')
                ->orWhere('title', 'like', '%'.$this->search.'%')))
            ->when($this->discipline !== '', fn ($query) => $query->where('discipline', $this->discipline))
            ->when($this->setId !== '', fn ($query) => $query->whereHas('revisions', fn ($query) => $query->where('plan_set_id', $this->setId)))
            ->orderBy('sort_index')
            ->orderBy('sheet_number')
            ->limit(min($this->perPage, 240))
            ->get();

        return view('plans::livewire.sheets.index', [
            'sheets' => $sheets,
            'sets' => PlanSet::query()->whereBelongsTo($this->project)->orderBy('name')->get(['id', 'name']),
            'disciplines' => PlanSheet::query()->whereBelongsTo($this->project)->whereNotNull('discipline')->distinct()->orderBy('discipline')->pluck('discipline'),
            'hasMore' => $sheets->count() === min($this->perPage, 240) && $this->perPage < 240,
        ]);
    }
}
