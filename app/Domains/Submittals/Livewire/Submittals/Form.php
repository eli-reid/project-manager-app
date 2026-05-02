<?php

namespace App\Domains\Submittals\Livewire\Submittals;

use App\Core\Identity\Models\User;
use App\Domains\Documents\Models\Document;
use App\Domains\Projects\Models\Project;
use App\Domains\Submittals\Models\Submittal;
use App\Domains\Submittals\Models\SubmittalApproval;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Submittal Form')]
class Form extends Component
{
    use AuthorizesRequests;

    public ?Submittal $submittal = null;

    #[Url]
    public string $projectId = '';

    #[Url]
    public string $returnTo = '';

    public bool $embedded = false;

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

        if ($submittal instanceof Submittal && ! $hasExplicitProjectContext) {
            $this->authorize('update', $submittal);
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

        if ($this->submittal instanceof Submittal) {
            $this->authorize('update', $this->submittal);

            $this->submittal->update($payload);
            $this->syncItems($this->submittal, $validated['items']);
            $this->syncApprovals($this->submittal, $validated['reviewerIds']);
            $this->syncDocuments($this->submittal, $validated['documentIds'] ?? []);

            session()->flash('success', 'Submittal updated successfully.');
            $this->redirectAfterSave($this->submittal);

            return;
        }

        $this->authorize('create', Submittal::class);

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

            $availableDocuments = Document::query()
                ->projectOwned()
                ->ownedByProject($this->projectId)
                ->orderBy('title')
                ->get(['id', 'title', 'original_name']);

            if ($selectedProject instanceof Project && Auth::user()?->can('manageProjectDocuments', [Document::class, $selectedProject])) {
                $uploadDocumentUrl = route('admin.projects.show', [
                    'project' => $selectedProject,
                    'tab' => 'documents',
                ]);
            }
        }

        return view('submittals::livewire.user.submittals.form', [
            'projects' => $projects,
            'reviewers' => User::query()
                ->where('is_active', true)
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->get(['id', 'first_name', 'last_name', 'email']),
            'availableDocuments' => $availableDocuments,
            'uploadDocumentUrl' => $uploadDocumentUrl,
            'cancelUrl' => $this->cancelUrl(),
            'embedded' => $this->embedded,
            'isProjectLocked' => $this->embedded && $selectedProject instanceof Project,
            'selectedProjectLabel' => $selectedProject instanceof Project
                ? trim($selectedProject->name.' ('.($selectedProject->project_number ?? 'N/A').')')
                : '',
        ]);
    }

    private function cancelUrl(): string
    {
        if ($this->returnTo !== '') {
            return $this->returnTo;
        }

        if ($this->submittal instanceof Submittal) {
            return route('submittals.show', $this->submittal);
        }

        return route('submittals.index');
    }

    private function redirectAfterSave(Submittal $submittal): void
    {
        if ($this->returnTo !== '') {
            $this->redirect($this->returnTo, navigate: true);

            return;
        }

        $this->redirectRoute('submittals.show', $submittal);
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
        $allowedDocumentIds = Document::query()
            ->projectOwned()
            ->ownedByProject((string) $submittal->project_id)
            ->whereIn('id', $selectedDocumentIds)
            ->pluck('id')
            ->values()
            ->all();

        $submittal->documents()->sync($allowedDocumentIds);
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
