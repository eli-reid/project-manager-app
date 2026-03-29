<?php

namespace App\Domains\Documents\Livewire\Admin\Projects;

use App\Core\User\Models\User;
use App\Domains\Documents\Models\Document;
use App\Domains\Documents\Services\DocumentService;
use App\Domains\Projects\Models\Project;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\WithFileUploads;

class DocumentsTab extends Component
{
    use AuthorizesRequests;
    use WithFileUploads;

    public Project $project;

    public string $title = '';

    public string $description = '';

    public string $search = '';

    public ?string $editingDocumentId = null;

    public mixed $file = null;

    public function mount(Project $project): void
    {
        $this->project = $project;
        $this->authorize('view', $project);
        $this->authorize('viewAny', Document::class);
    }

    public function save(DocumentService $documentService): void
    {
        $this->authorize('manageProjectDocuments', [Document::class, $this->project]);
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
                ->projectOwned()
                ->ownedByProject((string) $this->project->id)
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
            $documentService->uploadProjectDocument(
                $this->project,
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
            ->projectOwned()
            ->ownedByProject((string) $this->project->id)
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
            ->projectOwned()
            ->ownedByProject((string) $this->project->id)
            ->findOrFail($documentId);

        $this->authorize('delete', $document);

        $documentService->deleteDocument($document);

        if ($this->editingDocumentId === $documentId) {
            $this->resetForm();
        }
    }

    public function cancelEdit(): void
    {
        $this->resetForm();
    }

    public function render()
    {
        $documentsQuery = Document::query()
            ->projectOwned()
            ->ownedByProject((string) $this->project->id)
            ->with('uploadedBy:id,first_name,last_name')
            ->latest();

        if ($this->search !== '') {
            $documentsQuery->where(function ($query): void {
                $query->where('title', 'like', '%'.$this->search.'%')
                    ->orWhere('original_name', 'like', '%'.$this->search.'%');
            });
        }

        return view('documents::livewire.admin.projects.documents-tab', [
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
