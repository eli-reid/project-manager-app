<?php

namespace App\Domains\Documents\Livewire\User\Documents;

use App\Domains\Documents\Models\Document;
use App\Domains\Documents\Models\DocumentShare;
use App\Domains\Documents\Services\DocumentShareService;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Modal;
use Livewire\Component;

#[Modal]
class ShareManager extends Component
{
    use AuthorizesRequests;

    public string $documentId = '';

    public string $sharePassword = '';

    public ?string $expiresAt = null;

    public ?int $maxDownloads = null;

    public string $accessNotes = '';

    public bool $showForm = false;

    /**
     * @var DocumentShare[]
     */
    public array $shares = [];

    public function mount(string $documentId): void
    {
        $this->documentId = $documentId;
        $this->loadShares();
    }

    public function loadShares(): void
    {
        $document = Document::findOrFail($this->documentId);
        $this->authorize('view', $document);

        $this->shares = $document->shares()->with('createdBy')->get()->toArray();
    }

    public function createShare(DocumentShareService $shareService): void
    {
        $document = Document::findOrFail($this->documentId);
        $this->authorize('share', $document);

        $this->validate([
            'sharePassword' => 'nullable|string|min:4',
            'expiresAt' => 'nullable|date|after:now',
            'maxDownloads' => 'nullable|integer|min:1',
            'accessNotes' => 'nullable|string|max:500',
        ]);

        $options = array_filter([
            'password' => $this->sharePassword ?: null,
            'expires_at' => $this->expiresAt ? Carbon::parse($this->expiresAt) : null,
            'max_downloads' => $this->maxDownloads,
            'access_notes' => $this->accessNotes ?: null,
        ]);

        $shareService->createShare($document, Auth::user(), $options);

        $this->reset('sharePassword', 'expiresAt', 'maxDownloads', 'accessNotes', 'showForm');
        $this->loadShares();
        $this->dispatch('share-created');
    }

    public function toggleShare(string $shareId, DocumentShareService $shareService): void
    {
        $share = DocumentShare::findOrFail($shareId);
        $document = $share->document;

        $this->authorize('share', $document);

        $shareService->toggleShare($share, ! $share->is_active);
        $this->loadShares();
        $this->dispatch('share-toggled');
    }

    public function deleteShare(string $shareId, DocumentShareService $shareService): void
    {
        $share = DocumentShare::findOrFail($shareId);
        $document = $share->document;

        $this->authorize('share', $document);

        $shareService->deleteShare($share);
        $this->loadShares();
        $this->dispatch('share-deleted');
    }

    public function getShareUrlProperty(): string
    {
        return route('share.view', ['token' => $this->documentId]);
    }

    public function render()
    {
        return view('documents::livewire.user.documents.share-manager');
    }
}
