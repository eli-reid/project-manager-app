<?php

namespace App\Domains\Submittals\Livewire\Submittals;

use App\Core\Identity\Models\User;
use App\Domains\Documents\Contracts\DocumentOrchestratorContract;
use App\Domains\Documents\Contracts\ProjectDocumentLibraryContract;
use App\Domains\Documents\Models\Document;
use App\Domains\Projects\Models\Project;
use App\Domains\Submittals\Models\Submittal;
use App\Domains\Submittals\Models\SubmittalApproval;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\WithFileUploads;

#[Layout('layouts.app')]
#[Title('Submittal Form')]
class Form extends Component
{
    use AuthorizesRequests;
    use WithFileUploads;

    public ?Submittal $submittal = null;

    #[Url]
    public string $projectId = '';

    #[Url]
    public string $returnTo = '';

    public bool $embedded = false;

    public bool $showUploadModal = false;

    public string $uploadTitle = '';

    public string $uploadDescription = '';

    public mixed $uploadFile = null;

    public int $uploadMaxKilobytes = 0;

    /**
     * @var array<int, string>
     */
    public array $uploadAllowedExtensions = [];

    public string $type = '';

    public string $specReference = '';

    public string $vendor = '';

    public ?string $needByDate = null;

    /**
     * @var array<int, array{description:string, manufacturer:?string, model:?string, part_number:?string, quantity:?string, unit:?string}>
     */
    public array $items = [];

    /**
     * @var array<int, string>
     */
    public array $reviewerIds = [];

    /**
     * @var array<int, string>
     */
    public array $documentIds = [];

    public function mount(?Submittal $submittal = null, ?string $projectId = null, ?string $returnTo = null, ?bool $embedded = null): void
    {
        $this->submittal = $submittal;

        if ($embedded !== null) {
            $this->embedded = $embedded;
        }

        if ($returnTo !== null && $this->isSafeReturnPath($returnTo)) {
            $this->returnTo = $returnTo;
        }

        $hasExplicitProjectContext = $projectId !== null && $projectId !== '';

        if ($submittal instanceof Submittal && $submittal->exists && ! $hasExplicitProjectContext) {
            $this->authorizeWithTrace('update', $submittal, 'mount.edit');
            $this->projectId = (string) $submittal->project_id;
            $this->type = (string) $submittal->type;
            $this->specReference = (string) ($submittal->spec_reference ?? '');
            $this->vendor = (string) ($submittal->vendor ?? '');
            $this->needByDate = $submittal->need_by_date?->format('Y-m-d');

            $this->items = $submittal->items()
                ->orderBy('created_at')
                ->get(['description', 'manufacturer', 'model', 'part_number', 'quantity', 'unit'])
                ->map(fn ($item): array => [
                    'description' => (string) $item->description,
                    'manufacturer' => $item->manufacturer,
                    'model' => $item->model,
                    'part_number' => $item->part_number,
                    'quantity' => $item->quantity !== null ? (string) $item->quantity : null,
                    'unit' => $item->unit,
                ])
                ->values()
                ->all();

            $this->reviewerIds = $submittal->approvals()
                ->orderBy('step')
                ->pluck('reviewer_id')
                ->values()
                ->all();

            $this->documentIds = $submittal->documents()
                ->pluck('documents.id')
                ->values()
                ->all();

            if ($this->items === []) {
                $this->items = [$this->emptyItemRow()];
            }

            return;
        }

        $this->items = [$this->emptyItemRow()];

        if ($projectId !== null && $projectId !== '') {
            $this->projectId = $projectId;
        }

        $this->syncUploadConstraints();
    }

    public function openUploadModal(): void
    {
        if ($this->projectId === '') {
            return;
        }

        $project = Project::query()->find($this->projectId);

        if (! $project instanceof Project) {
            return;
        }

        $this->authorizeWithTrace('manageProjectDocuments', [Document::class, $project], 'openUploadModal');

        $this->showUploadModal = true;
    }

    public function uploadDocument(DocumentOrchestratorContract $documentOrchestrator): void
    {
        $project = Project::query()->find($this->projectId);

        if (! $project instanceof Project) {
            return;
        }

        $this->authorizeWithTrace('manageProjectDocuments', [Document::class, $project], 'uploadDocument');

        $rules = $documentOrchestrator->validationRules();

        $this->validate([
            'uploadTitle' => ['required', 'string', 'max:255'],
            'uploadDescription' => ['nullable', 'string', 'max:1000'],
            'uploadFile' => ['required', 'file', 'max:'.$rules['max_kilobytes'], 'mimes:'.implode(',', $rules['allowed_extensions'])],
        ]);

        /** @var User $user */
        $user = Auth::user();

        $document = $documentOrchestrator->uploadProjectDocument(
            $project,
            $user,
            $this->uploadFile,
            [
                'title' => $this->uploadTitle,
                'description' => $this->uploadDescription !== '' ? $this->uploadDescription : null,
            ]
        );

        $this->documentIds[] = (string) $document->id;

        $this->resetUploadModal();
    }

    public function resetUploadModal(): void
    {
        $this->uploadTitle = '';
        $this->uploadDescription = '';
        $this->uploadFile = null;
        $this->resetValidation(['uploadTitle', 'uploadDescription', 'uploadFile']);
        $this->showUploadModal = false;
        $this->dispatch('submittal-upload-reset');
    }

    public function addItem(): void
    {
        $this->items[] = $this->emptyItemRow();
    }

    public function removeItem(int $index): void
    {
        if (! array_key_exists($index, $this->items)) {
            return;
        }

        unset($this->items[$index]);
        $this->items = array_values($this->items);

        if ($this->items === []) {
            $this->items[] = $this->emptyItemRow();
        }
    }

    public function save(): void
    {
        if ($this->submittal instanceof Submittal && ! $this->submittal->exists) {
            $this->submittal = null;
        }

        Log::info('Submittal form save invoked.', [
            'user_id' => Auth::id(),
            'submittal_id' => $this->submittal?->id,
            'submittal_exists' => $this->submittal?->exists ?? false,
            'project_id' => $this->projectId,
            'embedded' => $this->embedded,
            'return_to' => $this->returnTo,
        ]);

        $validated = $this->validate([
            'projectId' => ['required', 'string', 'exists:projects,id'],
            'type' => ['required', 'string', 'max:120'],
            'specReference' => ['nullable', 'string', 'max:120'],
            'vendor' => ['nullable', 'string', 'max:120'],
            'needByDate' => ['nullable', 'date'],
            'reviewerIds' => ['required', 'array', 'min:1'],
            'reviewerIds.*' => ['string', 'exists:users,id', 'distinct'],
            'documentIds' => ['nullable', 'array'],
            'documentIds.*' => ['string', 'exists:documents,id', 'distinct'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.manufacturer' => ['nullable', 'string', 'max:120'],
            'items.*.model' => ['nullable', 'string', 'max:120'],
            'items.*.part_number' => ['nullable', 'string', 'max:120'],
            'items.*.quantity' => ['nullable', 'numeric', 'min:0'],
            'items.*.unit' => ['nullable', 'string', 'max:20'],
        ]);

        $payload = [
            'project_id' => $validated['projectId'],
            'type' => $validated['type'],
            'spec_reference' => $validated['specReference'] ?: null,
            'vendor' => $validated['vendor'] ?: null,
            'need_by_date' => $validated['needByDate'],
        ];

        if ($this->submittal instanceof Submittal && $this->submittal->exists) {
            $this->authorizeWithTrace('update', $this->submittal, 'save.update');

            $this->submittal->update($payload);
            $this->syncItems($this->submittal, $validated['items']);
            $this->syncApprovals($this->submittal, $validated['reviewerIds']);
            $this->syncDocuments($this->submittal, $validated['documentIds'] ?? []);

            session()->flash('success', 'Submittal updated successfully.');
            $this->redirectAfterSave($this->submittal);

            return;
        }

        $this->authorizeWithTrace('create', Submittal::class, 'save.create');

        $created = Submittal::query()->create([
            ...$payload,
            'status' => Submittal::STATUS_DRAFT,
            'submitted_by_id' => (string) Auth::id(),
        ]);

        $this->syncItems($created, $validated['items']);
        $this->syncApprovals($created, $validated['reviewerIds']);
        $this->syncDocuments($created, $validated['documentIds'] ?? []);

        session()->flash('success', 'Submittal created successfully.');
        $this->redirectAfterSave($created);
    }

    public function render()
    {
        $projects = Project::query()->orderBy('name')->get(['id', 'name', 'project_number']);

        $availableDocuments = collect();
        $uploadDocumentUrl = null;
        $selectedProject = null;

        if ($this->projectId !== '') {
            $selectedProject = $projects->firstWhere('id', $this->projectId);

            $availableDocuments = app(ProjectDocumentLibraryContract::class)
                ->listProjectAccessible($this->projectId);

            $canUploadDocument = $selectedProject instanceof Project
                && Auth::user()?->can('manageProjectDocuments', [Document::class, $selectedProject]);
        }

        $uploadMaxFileSizeLabel = $this->uploadMaxKilobytes >= 1024
            ? number_format($this->uploadMaxKilobytes / 1024, 1).' MB'
            : $this->uploadMaxKilobytes.' KB';

        return view('submittals::livewire.user.submittals.form', [
            'projects' => $projects,
            'reviewers' => User::query()
                ->where('is_active', true)
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->get(['id', 'first_name', 'last_name', 'email']),
            'availableDocuments' => $availableDocuments,
            'canUploadDocument' => $canUploadDocument ?? false,
            'uploadMaxFileSizeLabel' => $uploadMaxFileSizeLabel,
            'uploadAllowedExtensionsLabel' => strtoupper(implode(', ', $this->uploadAllowedExtensions)),
            'uploadAcceptAttribute' => collect($this->uploadAllowedExtensions)->map(fn (string $ext): string => '.'.$ext)->implode(','),
            'cancelUrl' => $this->cancelUrl(),
            'embedded' => $this->embedded,
            'isProjectLocked' => $this->embedded && $selectedProject instanceof Project,
            'selectedProjectLabel' => $selectedProject instanceof Project
                ? trim($selectedProject->name.' ('.($selectedProject->project_number ?? 'N/A').')')
                : '',
        ]);
    }

    private function syncUploadConstraints(): void
    {
        $rules = app(DocumentOrchestratorContract::class)->validationRules();

        $this->uploadMaxKilobytes = max(1, (int) ($rules['max_kilobytes'] ?? 10240));
        $this->uploadAllowedExtensions = collect($rules['allowed_extensions'] ?? [])
            ->map(fn (string $extension): string => trim(strtolower($extension)))
            ->filter()
            ->values()
            ->all();
    }

    private function cancelUrl(): string
    {
        if ($this->returnTo !== '') {
            return $this->returnTo;
        }

        if ($this->submittal instanceof Submittal && $this->submittal->exists) {
            return route('submittals.show', $this->submittal);
        }

        return route('submittals.index');
    }

    private function redirectAfterSave(Submittal $submittal): void
    {
        if ($this->returnTo !== '') {
            $user = Auth::user();

            if ($user instanceof User) {
                $project = Project::query()->find((string) $submittal->project_id);

                $canReturnToProjectAdmin = $project instanceof Project
                    && $user->can('viewAny', Project::class)
                    && $user->can('view', $project);

                Log::info('Submittal post-save redirect decision.', [
                    'user_id' => $user->id,
                    'submittal_id' => $submittal->id,
                    'project_id' => $submittal->project_id,
                    'return_to' => $this->returnTo,
                    'can_return_to_project_admin' => $canReturnToProjectAdmin,
                ]);

                if ($canReturnToProjectAdmin) {
                    $this->redirect($this->returnTo, navigate: true);

                    return;
                }
            }
        }

        $this->redirectRoute('submittals.show', $submittal);
    }

    private function authorizeWithTrace(string $ability, mixed $arguments, string $context): void
    {
        $user = Auth::user();
        if (! $user instanceof User) {
            Log::warning('Submittal authorization denied: no authenticated user.', [
                'ability' => $ability,
                'context' => $context,
                'submittal_id' => $this->submittal?->id,
                'project_id' => $this->projectId,
            ]);

            throw new AuthorizationException('Authentication required.');
        }

        $response = Gate::forUser($user)->inspect($ability, $arguments);
        if ($response->allowed()) {
            return;
        }

        Log::warning('Submittal authorization denied.', [
            'ability' => $ability,
            'context' => $context,
            'user_id' => $user->id,
            'submittal_id' => $this->submittal?->id,
            'project_id' => $this->projectId,
            'embedded' => $this->embedded,
            'return_to' => $this->returnTo,
            'message' => $response->message(),
        ]);

        throw new AuthorizationException($response->message() ?: 'This action is unauthorized.');
    }

    private function isSafeReturnPath(string $path): bool
    {
        return Str::startsWith($path, '/') && ! Str::startsWith($path, '//');
    }

    /**
     * @param  array<int, array{description:string, manufacturer:?string, model:?string, part_number:?string, quantity?:string|float|int|null, unit:?string}>  $items
     */
    private function syncItems(Submittal $submittal, array $items): void
    {
        $submittal->items()->delete();

        $submittal->items()->createMany(
            collect($items)
                ->map(function (array $item): array {
                    return [
                        'description' => $item['description'],
                        'manufacturer' => $item['manufacturer'] ?: null,
                        'model' => $item['model'] ?: null,
                        'part_number' => $item['part_number'] ?: null,
                        'quantity' => $item['quantity'] !== null && $item['quantity'] !== '' ? (float) $item['quantity'] : null,
                        'unit' => $item['unit'] ?: null,
                        'status' => 'pending',
                    ];
                })
                ->all()
        );
    }

    /**
     * @param  array<int, string>  $reviewerIds
     */
    private function syncApprovals(Submittal $submittal, array $reviewerIds): void
    {
        $submittal->approvals()->delete();

        foreach (array_values($reviewerIds) as $index => $reviewerId) {
            $submittal->approvals()->create([
                'step' => $index + 1,
                'reviewer_id' => $reviewerId,
                'status' => SubmittalApproval::STATUS_PENDING,
            ]);
        }

        $submittal->update([
            'current_reviewer_id' => $reviewerIds[0] ?? null,
        ]);
    }

    /**
     * @param  array<int, string>  $selectedDocumentIds
     */
    private function syncDocuments(Submittal $submittal, array $selectedDocumentIds): void
    {
        $allowedDocumentIds = app(ProjectDocumentLibraryContract::class)
            ->allowedDocumentIdsForProject((string) $submittal->project_id, $selectedDocumentIds);

        $submittal->documents()->syncWithPivotValues($allowedDocumentIds, [
            'document_role' => Submittal::DOCUMENT_ROLE_REFERENCE,
            'document_status' => Submittal::DOCUMENT_STATUS_ACTIVE,
            'revision' => null,
            'discipline' => null,
        ]);
    }

    /**
     * @return array{description:string, manufacturer:?string, model:?string, part_number:?string, quantity:?string, unit:?string}
     */
    private function emptyItemRow(): array
    {
        return [
            'description' => '',
            'manufacturer' => null,
            'model' => null,
            'part_number' => null,
            'quantity' => null,
            'unit' => null,
        ];
    }
}
