<?php

namespace App\Core\Assets\Livewire;

use App\Core\Assets\Contracts\AssetOrchestratorContract;
use App\Core\Assets\DTOs\AssetMeta;
use App\Core\Assets\DTOs\AssetReferenceTarget;
use App\Core\Identity\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\WithFileUploads;

class AssetUpload extends Component
{
    use WithFileUploads;

    public mixed $assetFile = null;

    public ?string $title = null;

    // folder path where the asset should be stored (e.g. "projects/123/plans")
    public string $folder = '';

    public string $referencerType = '';

    public string $referencerId = '';

    public string $role = AssetReferenceTarget::ROLE_PRIMARY;

    public function mount(string $folder = '', string $referencerType = '', string $referencerId = '', string $role = AssetReferenceTarget::ROLE_PRIMARY): void
    {
        $this->folder = $folder;
        $this->referencerType = $referencerType;
        $this->referencerId = $referencerId;
        $this->role = $role;
    }

    public function render(): View
    {
        return view('assets::livewire.asset-upload');
    }

    public function saveAsset(AssetOrchestratorContract $orchestrator): void
    {
        $rules = $orchestrator->validationRules($this->referencerType !== '' ? $this->referencerType : null);

        $this->validate([
            'assetFile' => ['required', 'file', 'max:'.$rules['max_kilobytes'], 'mimes:'.implode(',', $rules['allowed_extensions'])],
            'title' => 'nullable|string|max:255',
            'referencerType' => 'required|string|max:255',
            'referencerId' => 'required|string|max:255',
            'role' => 'required|string|max:255',
        ]);

        $uploader = Auth::user();
        abort_unless($uploader instanceof User, 401);

        $meta = AssetMeta::fromArray([
            'folder_path' => $this->folder ?: '',
        ]);

        $target = new AssetReferenceTarget($this->referencerType, $this->referencerId, $this->role);

        $asset = $orchestrator->upload($uploader, $this->assetFile, $target, $meta);

        // Preserve title for parent listeners
        $title = $this->title;

        // Reset local state
        $this->assetFile = null;
        $this->title = null;

        // Reset client-side file input
        $this->dispatch('assets-file-input-reset');

        // Notify client (browser) that an asset was uploaded. Parent Livewire
        // components can listen for this browser event and call server methods
        // to attach domain-specific pivot records. Use dispatchBrowserEvent to
        // ensure `event.detail` is an object with `id`, `payload`, and `title`.
        $this->dispatch('project-asset:uploaded', payload: $asset->toArray(), id: $asset->id, title: $title);
    }
}
