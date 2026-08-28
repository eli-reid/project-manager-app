<?php

namespace App\Core\Assets\Livewire;

use App\Core\Identity\Models\User;
use App\Core\Assets\Contracts\AssetOrchestratorContract;
use App\Core\Assets\DTOs\AssetMeta;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\WithFileUploads;

class AssetUpload extends Component
{
    use WithFileUploads;

    public mixed $assetFile = null;

    public ?string $title = null;

    // folder path where the asset should be stored (e.g. "projects/123/plans")
    public string $folder = '';

    public function mount(string $folder = ''): void
    {
        $this->folder = $folder;
    }

    public function render()
    {
        return view('assets::livewire.asset-upload');
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
            'folder_path' => $this->folder ?: '',
        ]);

        $asset = $orchestrator->uploadAsset($uploader, $this->assetFile, $meta);

        // Preserve title for parent listeners
        $title = $this->title;

        // Reset local state
        $this->assetFile = null;
        $this->title = null;

        // Reset client-side file input
        $this->dispatchBrowserEvent('assets-file-input-reset');

        // Notify client (browser) that an asset was uploaded. Parent Livewire
        // components can listen for this browser event and call server methods
        // to attach domain-specific pivot records. Use dispatchBrowserEvent to
        // ensure `event.detail` is an object with `id`, `payload`, and `title`.
        $this->dispatchBrowserEvent('project-asset:uploaded', [
            'payload' => $asset->toArray(),
            'id' => $asset->id,
            'title' => $title,
        ]);
    }
}
