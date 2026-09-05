<?php

namespace App\Domains\ChangeOrders\Livewire\Admin\ChangeOrders;

use App\Domains\ChangeOrders\Models\ChangeOrder;
use App\Domains\Documents\Contracts\ProjectDocumentLibraryContract;
use App\Domains\Projects\Models\Project;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Form extends Component
{
    use AuthorizesRequests;

    public ?ChangeOrder $changeOrder = null;

    public string $projectId = '';

    public string $title = '';

    public string $description = '';

    public string $laborAmount = '';

    public string $materialsAmount = '';

    public string $notes = '';

    public bool $embedded = false;

    public string $returnTo = '';

    /**
     * @var array<int, string>
     */
    public array $documentIds = [];

    /**
     * @var array<string, array{document_role:string, document_status:string, revision:?string, discipline:?string}>
     */
    public array $documentMetadata = [];

    private ProjectDocumentLibraryContract $projectDocumentLibrary;

    /**
     * Mount the component.
     */
    public function boot(ProjectDocumentLibraryContract $projectDocumentLibrary): void
    {
        $this->projectDocumentLibrary = $projectDocumentLibrary;
    }

    public function mount(?ChangeOrder $changeOrder = null, ?string $project_id = null, ?bool $embedded = null, ?string $returnTo = null): void
    {
        $this->changeOrder = $changeOrder;

        if ($embedded !== null) {
            $this->embedded = $embedded;
        }

        if (is_string($returnTo)) {
            $this->returnTo = $returnTo;
        }

        if ($changeOrder instanceof ChangeOrder && $changeOrder->exists) {
            $this->authorize('update', $changeOrder);

            $this->projectId = (string) $changeOrder->project_id;
            $this->title = (string) $changeOrder->title;
            $this->description = (string) ($changeOrder->description ?? '');
            $this->laborAmount = (string) (float) $changeOrder->labor_amount;
            $this->materialsAmount = (string) (float) $changeOrder->materials_amount;
            $this->notes = (string) ($changeOrder->notes ?? '');

            $this->documentIds = $changeOrder->documents()->pluck('documents.id')->values()->all();
            $this->documentMetadata = $changeOrder->documents()
                ->get()
                ->mapWithKeys(fn ($document): array => [
                    (string) $document->id => [
                        'document_role' => (string) ($document->pivot?->document_role ?? ChangeOrder::DOCUMENT_ROLE_REFERENCE),
                        'document_status' => (string) ($document->pivot?->document_status ?? ChangeOrder::DOCUMENT_STATUS_ACTIVE),
                        'revision' => $this->normalizeNullableString($document->pivot?->revision),
                        'discipline' => $this->normalizeNullableString($document->pivot?->discipline),
                    ],
                ])
                ->all();

            return;
        }

        $this->authorize('create', ChangeOrder::class);
        $this->projectId = is_string($project_id) ? $project_id : '';
    }

    public function save(): void
    {
        $this->updatedDocumentIds();

        $validated = $this->validate([
            'projectId' => ['required', 'string', 'exists:projects,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'laborAmount' => ['nullable', 'numeric', 'min:0'],
            'materialsAmount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'documentIds' => ['nullable', 'array'],
            'documentIds.*' => ['string', 'exists:documents,id', 'distinct'],
            'documentMetadata' => ['nullable', 'array'],
            'documentMetadata.*.document_role' => ['nullable', 'string', 'in:'.implode(',', ChangeOrder::allowedDocumentRoles())],
            'documentMetadata.*.document_status' => ['nullable', 'string', 'in:'.implode(',', ChangeOrder::allowedDocumentStatuses())],
            'documentMetadata.*.revision' => ['nullable', 'string', 'max:40'],
            'documentMetadata.*.discipline' => ['nullable', 'string', 'max:60'],
        ]);

        $payload = [
            'project_id' => $validated['projectId'],
            'title' => $validated['title'],
            'description' => $validated['description'] !== '' ? $validated['description'] : null,
            'labor_amount' => (float) ($validated['laborAmount'] !== '' ? $validated['laborAmount'] : 0),
            'materials_amount' => (float) ($validated['materialsAmount'] !== '' ? $validated['materialsAmount'] : 0),
            'notes' => $validated['notes'] !== '' ? $validated['notes'] : null,
        ];

        if ($this->changeOrder instanceof ChangeOrder && $this->changeOrder->exists) {
            $this->authorize('update', $this->changeOrder);

            $this->changeOrder->fill($payload)->recalculateTotal()->save();
            $this->syncDocuments($this->changeOrder, $validated['documentIds'] ?? []);

            if ($this->embedded && $this->returnTo !== '') {
                $this->redirect($this->returnTo, navigate: true);

                return;
            }

            $this->redirectRoute('admin.change-orders.show', $this->changeOrder);

            return;
        }

        $this->authorize('create', ChangeOrder::class);

        $created = ChangeOrder::query()->create([
            ...$payload,
            'status' => ChangeOrder::STATUS_DRAFT,
            'requested_by_id' => Auth::id(),
            'total_amount' => round((float) $payload['labor_amount'] + (float) $payload['materials_amount'], 2),
        ]);

        $this->syncDocuments($created, $validated['documentIds'] ?? []);

        if ($this->embedded && $this->returnTo !== '') {
            $this->redirect($this->returnTo, navigate: true);

            return;
        }

        $this->redirectRoute('admin.change-orders.show', $created);
    }

    public function updatedDocumentIds(): void
    {
        $selectedDocumentIds = collect($this->documentIds)
            ->filter(fn (mixed $id): bool => is_string($id) && $id !== '')
            ->values()
            ->all();

        $this->documentMetadata = collect($this->documentMetadata)
            ->only($selectedDocumentIds)
            ->map(function (mixed $metadata): array {
                if (! is_array($metadata)) {
                    return $this->defaultDocumentMetadata();
                }

                return [
                    'document_role' => $this->normalizeRole((string) ($metadata['document_role'] ?? ChangeOrder::DOCUMENT_ROLE_REFERENCE)),
                    'document_status' => $this->normalizeStatus((string) ($metadata['document_status'] ?? ChangeOrder::DOCUMENT_STATUS_ACTIVE)),
                    'revision' => $this->normalizeNullableString($metadata['revision'] ?? null),
                    'discipline' => $this->normalizeNullableString($metadata['discipline'] ?? null),
                ];
            })
            ->all();

        foreach ($selectedDocumentIds as $documentId) {
            if (! array_key_exists($documentId, $this->documentMetadata)) {
                $this->documentMetadata[$documentId] = $this->defaultDocumentMetadata();
            }
        }
    }

    /**
     * Render the component.
     */
    public function render()
    {
        $availableDocuments = collect();
        if ($this->projectId !== '') {
            $availableDocuments = $this->projectDocumentLibrary->listProjectAccessible($this->projectId);
        }

        return view('change-orders::livewire.admin.change-orders.form', [
            'projects' => Project::query()->orderBy('name')->get(['id', 'name', 'project_number']),
            'availableDocuments' => $availableDocuments,
            'isEditing' => $this->changeOrder instanceof ChangeOrder && $this->changeOrder->exists,
            'embedded' => $this->embedded,
        ]);
    }

    /**
     * @param  array<int, string>  $selectedDocumentIds
     */
    private function syncDocuments(ChangeOrder $changeOrder, array $selectedDocumentIds): void
    {
        $allowedDocumentIds = $this->projectDocumentLibrary
            ->allowedDocumentIdsForProject((string) $changeOrder->project_id, $selectedDocumentIds);

        $pivotPayload = collect($allowedDocumentIds)
            ->mapWithKeys(fn (string $documentId): array => [
                $documentId => $this->documentMetadataValuesForDocument($documentId),
            ])
            ->all();

        $changeOrder->documents()->sync($pivotPayload);
    }

    /**
     * @return array{document_role:string, document_status:string, revision:?string, discipline:?string}
     */
    private function documentMetadataValuesForDocument(string $documentId): array
    {
        $metadata = $this->documentMetadata[$documentId] ?? [];

        return [
            'document_role' => $this->normalizeRole((string) ($metadata['document_role'] ?? ChangeOrder::DOCUMENT_ROLE_REFERENCE)),
            'document_status' => $this->normalizeStatus((string) ($metadata['document_status'] ?? ChangeOrder::DOCUMENT_STATUS_ACTIVE)),
            'revision' => $this->normalizeNullableString($metadata['revision'] ?? null),
            'discipline' => $this->normalizeNullableString($metadata['discipline'] ?? null),
        ];
    }

    /**
     * @return array{document_role:string, document_status:string, revision:?string, discipline:?string}
     */
    private function defaultDocumentMetadata(): array
    {
        return [
            'document_role' => ChangeOrder::DOCUMENT_ROLE_REFERENCE,
            'document_status' => ChangeOrder::DOCUMENT_STATUS_ACTIVE,
            'revision' => null,
            'discipline' => null,
        ];
    }

    private function normalizeRole(string $role): string
    {
        return in_array($role, ChangeOrder::allowedDocumentRoles(), true)
            ? $role
            : ChangeOrder::DOCUMENT_ROLE_REFERENCE;
    }

    private function normalizeStatus(string $status): string
    {
        return in_array($status, ChangeOrder::allowedDocumentStatuses(), true)
            ? $status
            : ChangeOrder::DOCUMENT_STATUS_ACTIVE;
    }

    private function normalizeNullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $normalized = trim($value);

        return $normalized !== '' ? $normalized : null;
    }
}
