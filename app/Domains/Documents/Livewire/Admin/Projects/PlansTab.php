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

class PlansTab extends Component
{
    use AuthorizesRequests;
    use WithFileUploads;

    /**
     * Top-level folder_path segment that marks a Document as a project plan/blueprint.
     */
    public const PLANS_FOLDER = 'Plans';

    public Project $project;

    public string $title = '';

    public string $description = '';

    public string $set = '';

    public string $search = '';

    public string $activeSet = '';

    public ?string $editingDocumentId = null;

    public mixed $file = null;

    public int $maxKilobytes = 0;

    public bool $canManageProjectPlans = false;

    public bool $canUpdateProjectPlans = false;

    public bool $canDeleteProjectPlans = false;

    /**
     * @var array<int, string>
     */
    public array $allowedExtensions = [];

    public function mount(Project $project): void
    {
        $this->project = $project;
        $this->authorize('view', $project);
        $this->authorize('viewAny', Document::class);

        $this->syncCapabilities();
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
            'set' => ['nullable', 'string', 'max:255'],
            'file' => ['nullable', 'file', 'max:'.$rules['max_kilobytes'], 'mimes:'.implode(',', $rules['allowed_extensions'])],
        ];

        if ($this->editingDocumentId === null) {
            $validationRules['file'][0] = 'required';
        }

        $this->validate($validationRules);

        $folderPath = $this->folderPathForSet($this->set);

        if ($this->editingDocumentId !== null) {
            $document = $this->findOwnedPlanOrFail($projectDocumentLibrary, $this->editingDocumentId);

            $this->authorize('update', $document);

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
                    'folder_path' => $folderPath,
                ]
            );
        }

        $this->resetForm();
    }

    public function edit(string $documentId, ProjectDocumentLibraryContract $projectDocumentLibrary): void
    {
        $document = $this->findOwnedPlanOrFail($projectDocumentLibrary, $documentId);

        $this->authorize('update', $document);

        $this->editingDocumentId = $document->id;
        $this->title = $document->title;
        $this->description = (string) ($document->description ?? '');
        $this->set = $this->setLabelFromFolderPath($document->folder_path);
        $this->file = null;
    }

    public function delete(string $documentId, DocumentOrchestratorContract $documentOrchestrator, ProjectDocumentLibraryContract $projectDocumentLibrary): void
    {
        $document = $this->findOwnedPlanOrFail($projectDocumentLibrary, $documentId);

        $this->authorize('delete', $document);

        $documentOrchestrator->deleteDocument($document);

        if ($this->editingDocumentId === $documentId) {
            $this->resetForm();
        }
    }

    public function setActiveSet(string $set): void
    {
        $this->activeSet = $set;
    }

    public function cancelEdit(): void
    {
        $this->resetForm();
    }

    public function render()
    {
        $library = app(ProjectDocumentLibraryContract::class);

        $plans = $library->listProjectOwned((string) $this->project->id, $this->search !== '' ? $this->search : null)
            ->filter(fn (Document $document): bool => $this->isPlanDocument($document));

        if ($this->activeSet !== '') {
            $plans = $plans->filter(
                fn (Document $document): bool => $this->setLabelFromFolderPath($document->folder_path) === $this->activeSet
            );
        }

        $sets = $plans->isEmpty() && $this->activeSet === ''
            ? collect()
            : $library->listProjectOwned((string) $this->project->id)
                ->filter(fn (Document $document): bool => $this->isPlanDocument($document))
                ->map(fn (Document $document): string => $this->setLabelFromFolderPath($document->folder_path))
                ->filter()
                ->unique()
                ->sort()
                ->values();

        return view('documents::livewire.admin.projects.plans-tab', [
            'plans' => $plans->values(),
            'sets' => $sets,
            'maxFileSizeLabel' => $this->maxFileSizeLabel(),
            'allowedExtensionsLabel' => strtoupper(implode(', ', $this->allowedExtensions)),
            'acceptAttribute' => $this->acceptAttribute(),
        ]);
    }

    private function findOwnedPlanOrFail(ProjectDocumentLibraryContract $projectDocumentLibrary, string $documentId): Document
    {
        $document = $projectDocumentLibrary->findProjectOwnedOrFail((string) $this->project->id, $documentId);

        abort_unless($this->isPlanDocument($document), 404);

        return $document;
    }

    private function isPlanDocument(Document $document): bool
    {
        $folderPath = (string) ($document->folder_path ?? '');

        return $folderPath === self::PLANS_FOLDER || str_starts_with($folderPath, self::PLANS_FOLDER.'/');
    }

    private function folderPathForSet(string $set): string
    {
        $set = trim($set);

        return $set === '' ? self::PLANS_FOLDER : self::PLANS_FOLDER.'/'.$set;
    }

    private function setLabelFromFolderPath(?string $folderPath): string
    {
        $folderPath = (string) $folderPath;

        if ($folderPath === self::PLANS_FOLDER) {
            return '';
        }

        return str_starts_with($folderPath, self::PLANS_FOLDER.'/')
            ? substr($folderPath, strlen(self::PLANS_FOLDER) + 1)
            : '';
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

    private function syncCapabilities(): void
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (! $user instanceof User) {
            $this->canManageProjectPlans = false;
            $this->canUpdateProjectPlans = false;
            $this->canDeleteProjectPlans = false;

            return;
        }

        $this->canManageProjectPlans = (
            $user->hasPermission('documents.manage-project')
            || $user->hasPermission('projects.upload-documents')
        ) && (
            $user->hasPermission('documents.view')
            || $user->hasPermission('projects.view-documents')
        );

        $this->canUpdateProjectPlans = $this->canManageProjectPlans
            && $user->hasPermission('documents.update');

        $this->canDeleteProjectPlans = $user->isAdmin()
            || ($this->canManageProjectPlans && $user->hasPermission('documents.delete'));
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
        $this->set = '';
        $this->file = null;
        $this->resetValidation();
        $this->dispatch('project-plans-file-input-reset');
    }
}
