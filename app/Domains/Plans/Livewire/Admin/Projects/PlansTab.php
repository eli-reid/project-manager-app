<?php

declare(strict_types=1);

namespace App\Domains\Plans\Livewire\Admin\Projects;

use App\Core\Identity\Models\User;
use App\Core\Settings\Facades\Settings;
use App\Domains\Plans\Models\PlanSet;
use App\Domains\Plans\Services\PlanSetIngestionService;
use App\Domains\Projects\Models\Project;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\WithFileUploads;

final class PlansTab extends Component
{
    use AuthorizesRequests;
    use WithFileUploads;

    public Project $project;

    public string $name = '';

    public string $discipline = '';

    public string $search = '';

    public mixed $file = null;

    public bool $canUploadPlans = false;

    public bool $canUpdatePlans = false;

    public bool $canDeletePlans = false;

    public function mount(Project $project): void
    {
        $this->project = $project;
        $this->authorize('view', $project);
        $this->authorize('viewAny', PlanSet::class);
        $user = Auth::user();
        $this->canUploadPlans = $user instanceof User && $user->hasPermission('plans.upload');
        $this->canUpdatePlans = $user instanceof User && $user->hasPermission('plans.update');
        $this->canDeletePlans = $user instanceof User && $user->hasPermission('plans.delete');
    }

    public function save(PlanSetIngestionService $ingestion): void
    {
        $this->authorize('create', PlanSet::class);
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'discipline' => ['nullable', 'string', 'max:100'],
            'file' => ['required', 'file', 'mimes:pdf', 'max:'.Settings::get('plans.max_upload_kilobytes', 512000)->toInt()],
        ]);
        $actor = Auth::user();
        abort_unless($actor instanceof User, 401);
        $ingestion->ingest($this->project, $actor, $this->file, [
            'name' => $this->name,
            'discipline' => $this->discipline !== '' ? $this->discipline : null,
        ]);
        $this->reset(['name', 'discipline', 'file']);
        session()->flash('success', 'Plan set uploaded and queued for processing.');
    }

    public function render()
    {
        $sets = PlanSet::query()
            ->whereBelongsTo($this->project)
            ->when($this->search !== '', fn ($query) => $query->where('name', 'like', '%'.$this->search.'%'))
            ->latest()
            ->limit(100)
            ->get();

        return view('plans::livewire.admin.projects.plans-tab', [
            'sets' => $sets,
            'polling' => $sets->contains(fn (PlanSet $set): bool => in_array($set->status, [
                PlanSet::STATUS_PENDING, PlanSet::STATUS_SPLITTING, PlanSet::STATUS_RENDERING,
            ], true)),
        ]);
    }
}
