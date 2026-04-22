<?php

namespace App\Domains\Documents\Livewire\User\Documents;

use App\Core\Identity\Models\User;
use App\Domains\Documents\Models\Document;
use App\Domains\Documents\Models\DocumentShare;
use App\Domains\Documents\Services\DocumentService;
use App\Domains\Documents\Services\DocumentShareService;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\WithFileUploads;

#[Layout('layouts.app')]
#[Title('My Documents')]
class Index extends Component
{
    use AuthorizesRequests;
    use WithFileUploads;

    public string $title = '';

    public string $description = '';

    public string $search = '';

    public ?string $editingDocumentId = null;

    public mixed $file = null;

    public ?string $sharingDocumentId = null;

    public string $sharePassword = '';

    public ?string $shareExpiresAt = null;

    public ?int $shareMaxDownloads = null;

    public string $shareAccessNotes = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Document::class);

        $shareDocumentId = request()->query('share');

        if (is_string($shareDocumentId) && $shareDocumentId !== '') {
            $document = Document::query()
                ->userOwned()
                ->ownedByUser((string) Auth::id())
                ->find($shareDocumentId);

            if ($document !== null && Auth::user()?->can('share', $document)) {
                $this->sharingDocumentId = $document->id;
            }
        }
    }

    public function save(DocumentService $documentService): void
    {
        $this->authorize('create', Document::class);
        /** @var User $user */
        $user = Auth::user();

        $rules = $documentService->validationRules();
        $validationRules = [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'file' => ['nullable', 'file', 'max:'.$rules['max_kilobytes'], 'mimes:'.implode(',', $rules['allowed_extensions'])],
        ];

        if ($this->editingDocumentId === null) {
            $validationRules['file'][0] = 'required';
        }

        $this->validate($validationRules);

        if ($this->editingDocumentId !== null) {
            $document = Document::query()
                ->userOwned()
                ->ownedByUser((string) Auth::id())
                ->findOrFail($this->editingDocumentId);

            $this->authorize('update', $document);

            $document->update([
                'title' => $this->title,
                'description' => $this->description !== '' ? $this->description : null,
            ]);

            if ($this->file !== null) {
                $documentService->replaceFile($document, $this->file, $user);
            }
        } else {
            $documentService->uploadUserDocument(
                $user,
                $this->file,
                [
                    'title' => $this->title,
                    'description' => $this->description !== '' ? $this->description : null,
                ]
            );
        }

        $this->resetForm();
    }

    public function edit(string $documentId): void
    {
        $document = Document::query()
            ->userOwned()
            ->ownedByUser((string) Auth::id())
            ->findOrFail($documentId);

        $this->authorize('update', $document);

        $this->editingDocumentId = $document->id;
        $this->title = $document->title;
        $this->description = (string) ($document->description ?? '');
        $this->file = null;
    }

    public function delete(string $documentId, DocumentService $documentService): void
    {
        $document = Document::query()
            ->userOwned()
            ->ownedByUser((string) Auth::id())
            ->findOrFail($documentId);

        $this->authorize('delete', $document);

        $documentService->deleteDocument($document);

        if ($this->editingDocumentId === $documentId) {
            $this->resetForm();
        }
    }

    public function promote(string $documentId): void
    {
        $document = Document::query()
            ->userOwned()
            ->ownedByUser((string) Auth::id())
            ->findOrFail($documentId);

        $this->authorize('promoteToGlobal', $document);

        $document->update(['visibility' => Document::VISIBILITY_GLOBAL]);
    }

    public function demote(string $documentId): void
    {
        $document = Document::query()
            ->userOwned()
            ->ownedByUser((string) Auth::id())
            ->findOrFail($documentId);

        $this->authorize('demoteToPrivate', $document);

        $document->update(['visibility' => Document::VISIBILITY_PRIVATE]);
    }

    public function openSharePanel(string $documentId): void
    {
        $document = Document::query()
            ->userOwned()
            ->ownedByUser((string) Auth::id())
            ->findOrFail($documentId);

        $this->authorize('share', $document);

        $this->sharingDocumentId = $documentId;
    }

    public function closeSharePanel(): void
    {
        $this->sharingDocumentId = null;
        $this->resetShareForm();
    }

    public function createShare(DocumentShareService $documentShareService): void
    {
        if ($this->sharingDocumentId === null) {
            return;
        }

        $document = Document::query()
            ->userOwned()
            ->ownedByUser((string) Auth::id())
            ->findOrFail($this->sharingDocumentId);

        $this->authorize('share', $document);

        $this->validate([
            'sharePassword' => ['nullable', 'string', 'min:4'],
            'shareExpiresAt' => ['nullable', 'date', 'after:now'],
            'shareMaxDownloads' => ['nullable', 'integer', 'min:1'],
            'shareAccessNotes' => ['nullable', 'string', 'max:500'],
        ]);

        $options = [
            'password' => $this->sharePassword !== '' ? $this->sharePassword : null,
            'expires_at' => $this->shareExpiresAt !== null ? Carbon::parse($this->shareExpiresAt) : null,
            'max_downloads' => $this->shareMaxDownloads,
            'access_notes' => $this->shareAccessNotes !== '' ? $this->shareAccessNotes : null,
        ];

        /** @var User $user */
        $user = Auth::user();
        $documentShareService->createShare($document, $user, $options);

        $this->resetShareForm();
    }

    public function toggleShare(string $shareId, DocumentShareService $documentShareService): void
    {
        $share = DocumentShare::query()
            ->whereKey($shareId)
            ->whereHas('document.ownerUsers', fn ($query) => $query->where('users.id', Auth::id()))
            ->firstOrFail();

        $this->authorize('share', $share->document);

        $documentShareService->toggleShare($share, ! $share->is_active);
    }

    public function deleteShare(string $shareId, DocumentShareService $documentShareService): void
    {
        $share = DocumentShare::query()
            ->whereKey($shareId)
            ->whereHas('document.ownerUsers', fn ($query) => $query->where('users.id', Auth::id()))
            ->firstOrFail();

        $this->authorize('share', $share->document);

        $documentShareService->deleteShare($share);
    }

    public function cancelEdit(): void
    {
        $this->resetForm();
    }

    public function render()
    {
        $documentsQuery = Document::query()
            ->userOwned()
            ->ownedByUser((string) Auth::id())
            ->withCount('shares')
            ->latest();

        if ($this->search !== '') {
            $documentsQuery->where(function ($query): void {
                $query->where('title', 'like', '%'.$this->search.'%')
                    ->orWhere('original_name', 'like', '%'.$this->search.'%');
            });
        }

        $sharingDocument = null;
        if ($this->sharingDocumentId !== null) {
            $sharingDocument = Document::query()
                ->userOwned()
                ->ownedByUser((string) Auth::id())
                ->with([
                    'shares' => fn ($query) => $query->latest(),
                ])
                ->find($this->sharingDocumentId);
        }

        return view('documents::livewire.user.documents.index', [
            'documents' => $documentsQuery->get(),
            'sharingDocument' => $sharingDocument,
        ]);
    }

    private function resetForm(): void
    {
        $this->editingDocumentId = null;
        $this->title = '';
        $this->description = '';
        $this->file = null;
        $this->resetValidation();
        $this->dispatch('documents-file-input-reset');
    }

    private function resetShareForm(): void
    {
        $this->sharePassword = '';
        $this->shareExpiresAt = null;
        $this->shareMaxDownloads = null;
        $this->shareAccessNotes = '';
        $this->resetValidation([
            'sharePassword',
            'shareExpiresAt',
            'shareMaxDownloads',
            'shareAccessNotes',
        ]);
    }
}
