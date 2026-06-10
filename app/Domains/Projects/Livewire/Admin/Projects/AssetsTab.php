<?php

namespace App\Domains\Projects\Livewire\Admin\Projects;

use App\Core\Identity\Models\User;
use App\Domains\Projects\Models\Project;
use App\Domains\Projects\Models\ProjectAsset;
use App\Domains\Assets\Contracts\AssetOrchestratorContract;
use Illuminate\Foundation\Application;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Http\UploadedFile;

class AssetsTab extends Component
{
    use WithFileUploads;

    public Project $project;

    public ?UploadedFile $file = null;

    public ?string $title = null;

    public ?string $deletingId = null;

    protected $listeners = [
        'deleteProjectAsset',
    ];

    public function mount(Project $project): void
    {
        $this->project = $project;
    }

    public function render()
    {
        return view('projects::livewire.admin.projects.assets-tab', [
            'project' => $this->project,
            'assets' => ProjectAsset::query()->where('project_id', $this->project->id)->with('asset')->get(),
        ]);
    }

    public function upload(AssetOrchestratorContract $orchestrator): void
    {
        $this->validate([
            'file' => 'required|file',
            'title' => 'nullable|string|max:255',
        ]);

        $uploader = auth()->user() ?: User::first();

        $asset = $orchestrator->uploadAsset($uploader, $this->file);

        ProjectAsset::create([
            'id' => (string) \Illuminate\Support\Str::ulid(),
            'project_id' => $this->project->id,
            'asset_id' => $asset->id,
            'created_by_id' => $uploader?->id,
            'title' => $this->title,
        ]);

        $this->file = null;
        $this->title = null;

        $this->dispatchBrowserEvent('project-asset:uploaded');
    }

    public function deleteProjectAsset(string $id): void
    {
        $pa = ProjectAsset::find($id);

        if (! $pa) {
            $this->dispatchBrowserEvent('toast', ['type' => 'error', 'message' => 'Project asset not found.']);
            return;
        }

        // Resolve orchestrator from container to perform storage deletion
        $orchestrator = app(AssetOrchestratorContract::class);

        try {
            // delete underlying asset (orchestrator may remove file and asset row)
            $orchestrator->deleteAsset($pa->asset);
        } catch (\Throwable $e) {
            // If deleting asset fails, still remove the project pivot to avoid orphaned UI state
        }

        $pa->delete();

        $this->dispatchBrowserEvent('project-asset:deleted');
    }
}
