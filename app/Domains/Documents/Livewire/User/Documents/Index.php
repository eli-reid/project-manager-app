<?php

namespace App\Domains\Documents\Livewire\User\Documents;

use App\Core\User\Models\User;
use App\Domains\Documents\Models\Document;
use App\Domains\Documents\Services\DocumentService;
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

    public function mount(): void
    {
        $this->authorize('viewAny', Document::class);
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

    public function cancelEdit(): void
    {
        $this->resetForm();
    }

    public function render()
    {
        $documentsQuery = Document::query()
            ->userOwned()
            ->ownedByUser((string) Auth::id())
            ->latest();

        if ($this->search !== '') {
            $documentsQuery->where(function ($query): void {
                $query->where('title', 'like', '%'.$this->search.'%')
                    ->orWhere('original_name', 'like', '%'.$this->search.'%');
            });
        }

        return view('documents::livewire.user.documents.index', [
            'documents' => $documentsQuery->get(),
        ]);
    }

    private function resetForm(): void
    {
        $this->editingDocumentId = null;
        $this->title = '';
        $this->description = '';
        $this->file = null;
        $this->resetValidation();
    }
}
