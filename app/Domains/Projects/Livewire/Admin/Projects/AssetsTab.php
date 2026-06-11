<?php

namespace App\Domains\Projects\Livewire\Admin\Projects;

use App\Core\Identity\Models\User;
use App\Domains\Assets\Contracts\AssetOrchestratorContract;
use App\Domains\Assets\DTOs\AssetMeta;
use App\Domains\Projects\Models\Project;
use App\Domains\Projects\Models\ProjectAsset;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\WithFileUploads;

class AssetsTab extends Component
{
    use WithFileUploads;

    public Project $project;

    public mixed $assetFile = null;

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

    public function saveAsset(AssetOrchestratorContract $orchestrator): void
    {
        $rules = $orchestrator->validationRules();

        $this->validate([
            'assetFile' => ['required', 'file', 'max:'.$rules['max_kilobytes'], 'mimes:'.implode(',', $rules['allowed_extensions'])],
            'title' => 'nullable|string|max:255',
        ]);

        $uploader = auth()->user();
        abort_unless($uploader instanceof User, 401);

        $meta = AssetMeta::fromArray([
            'disk' => 'public',
            'folder_path' => 'projects/'.$this->project->id.'/libraries',
        ]);

        $asset = $orchestrator->uploadAsset($uploader, $this->assetFile, $meta);

        ProjectAsset::create([
            'id' => (string) Str::ulid(),
            'project_id' => $this->project->id,
            'asset_id' => $asset->id,
            'created_by_id' => $uploader?->id,
            'title' => $this->title,
        ]);

        $this->assetFile = null;
        $this->title = null;

        // Reset client-side file input and UI like DocumentsTab does
        $this->dispatch('project-documents-file-input-reset');
        $this->dispatch('project-asset:uploaded');
    }

    public function deleteProjectAsset(string $id): void
    {
        $pa = ProjectAsset::find($id);

        if (! $pa) {
            $this->dispatch('toast', type: 'error', message: 'Project asset not found.');

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

        $this->dispatch('project-asset:deleted');
    }
}
