<?php

namespace App\Domains\Documents\Livewire\Admin\Projects;

use App\Core\Identity\Models\User;
use App\Domains\Documents\Contracts\DocumentOrchestratorContract;
use App\Domains\Documents\Contracts\ProjectDocumentLibraryContract;
use App\Domains\Documents\Models\Document;
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

    public string $folderPath = '';

    public string $search = '';

    public ?string $editingDocumentId = null;

    public mixed $file = null;

    public int $maxKilobytes = 0;

    /**
     * @var array<int, string>
     */
    public array $allowedExtensions = [];

    public function mount(Project $project): void
    {
        $this->project = $project;
        $this->authorize('view', $project);
        $this->authorize('viewAny', Document::class);

        $this->syncUploadConstraints();
    }

    public function save(DocumentOrchestratorContract $documentOrchestrator, ProjectDocumentLibraryContract $projectDocumentLibrary): void
    {
        $this->authorize('manageProjectDocuments', [Document::class, $this->project]);
        /** @var User $user */
        $user = Auth::user();

        $rules = $documentOrchestrator->validationRules();
        $validationRules = [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'folderPath' => ['nullable', 'string', 'max:255'],
            'file' => ['nullable', 'file', 'max:'.$rules['max_kilobytes'], 'mimes:'.implode(',', $rules['allowed_extensions'])],
        ];

        if ($this->editingDocumentId === null) {
            $validationRules['file'][0] = 'required';
        }

        $this->validate($validationRules);

        if ($this->editingDocumentId !== null) {
            $document = $projectDocumentLibrary->findProjectOwnedOrFail((string) $this->project->id, $this->editingDocumentId);

            $this->authorize('update', $document);
            $folderPath = $this->folderPath !== '' ? $this->folderPath : null;

            $document->update([
                'title' => $this->title,
                'description' => $this->description !== '' ? $this->description : null,
            ]);

            if ($this->file !== null) {
                $documentOrchestrator->replaceFile($document, $this->file, $user, $folderPath);
            } else {
                $documentOrchestrator->moveDocument($document, $folderPath);
            }
        } else {
            $documentOrchestrator->uploadProjectDocument(
                $this->project,
                $user,
                $this->file,
                [
                    'title' => $this->title,
                    'description' => $this->description !== '' ? $this->description : null,
                    'folder_path' => $this->folderPath !== '' ? $this->folderPath : null,
                ]
            );
        }

        $this->resetForm();
    }

    public function edit(string $documentId): void
    {
        $document = app(ProjectDocumentLibraryContract::class)
            ->findProjectOwnedOrFail((string) $this->project->id, $documentId);

        $this->authorize('update', $document);

        $this->editingDocumentId = $document->id;
        $this->title = $document->title;
        $this->description = (string) ($document->description ?? '');
        $this->folderPath = (string) ($document->folder_path ?? '');
        $this->file = null;
    }

    public function delete(string $documentId, DocumentOrchestratorContract $documentOrchestrator): void
    {
        $document = app(ProjectDocumentLibraryContract::class)
            ->findProjectOwnedOrFail((string) $this->project->id, $documentId);

        $this->authorize('delete', $document);

        $documentOrchestrator->deleteDocument($document);

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
        $documents = app(ProjectDocumentLibraryContract::class)
            ->listProjectOwned((string) $this->project->id, $this->search !== '' ? $this->search : null);

        return view('documents::livewire.admin.projects.documents-tab', [
            'documents' => $documents,
            'maxFileSizeLabel' => $this->maxFileSizeLabel(),
            'allowedExtensionsLabel' => strtoupper(implode(', ', $this->allowedExtensions)),
            'acceptAttribute' => $this->acceptAttribute(),
        ]);
    }

    private function syncUploadConstraints(): void
    {
        $rules = app(DocumentOrchestratorContract::class)->validationRules();

        $this->maxKilobytes = max(1, (int) ($rules['max_kilobytes'] ?? 10240));
        $this->allowedExtensions = collect($rules['allowed_extensions'] ?? [])
            ->map(fn (string $extension): string => trim(strtolower($extension)))
            ->filter()
            ->values()
            ->all();
    }

    private function maxFileSizeLabel(): string
    {
        if ($this->maxKilobytes >= 1024) {
            return number_format($this->maxKilobytes / 1024, 1).' MB';
        }

        return $this->maxKilobytes.' KB';
    }

    private function acceptAttribute(): string
    {
        return collect($this->allowedExtensions)
            ->map(fn (string $extension): string => '.'.$extension)
            ->implode(',');
    }

    private function resetForm(): void
    {
        $this->editingDocumentId = null;
        $this->title = '';
        $this->description = '';
        $this->folderPath = '';
        $this->file = null;
        $this->resetValidation();
        $this->dispatch('project-documents-file-input-reset');
    }
}
