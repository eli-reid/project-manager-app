<?php

namespace App\Domains\RFIs\Livewire\Admin\Projects;

use App\Core\Identity\Models\User;
use App\Domains\Documents\Contracts\ProjectDocumentLibraryContract;
use App\Domains\Projects\Models\Project;
use App\Domains\RFIs\Models\RFI;
use App\Domains\RFIs\Services\RFILifecycleService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ProjectTab extends Component
{
    use AuthorizesRequests;

    public Project $project;

    /**
     * @var Collection<int, RFI>
     */
    public $rfis;

    public int $rfiCount = 0;

    public bool $isCreateMode = false;

    /**
     * @var array<int, string>
     */
    public array $documentIds = [];

    /**
     * @var array<string, array{document_role:string, document_status:string, revision:?string, discipline:?string}>
     */
    public array $documentMetadata = [];

    private ProjectDocumentLibraryContract $projectDocumentLibrary;

    // Form fields
    public string $subject = '';

    public string $body = '';

    public ?string $dueDate = null;

    public function boot(ProjectDocumentLibraryContract $projectDocumentLibrary): void
    {
        $this->projectDocumentLibrary = $projectDocumentLibrary;
    }

    public function mount(Project $project, $rfis = null, int $rfiCount = 0, bool $isCreateMode = false): void
    {
        $this->project = $project;
        $this->rfis = $rfis ?? collect();
        $this->rfiCount = $rfiCount;
        $this->isCreateMode = $isCreateMode;
    }

    public function submitRfi(RFILifecycleService $service): void
    {
        $this->authorize('create', RFI::class);

        $this->updatedDocumentIds();

        $this->validate([
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['nullable', 'string'],
            'dueDate' => ['nullable', 'date'],
            'documentIds' => ['nullable', 'array'],
            'documentIds.*' => ['string', 'exists:documents,id', 'distinct'],
            'documentMetadata' => ['nullable', 'array'],
            'documentMetadata.*.document_role' => ['nullable', 'string', 'in:'.implode(',', RFI::allowedDocumentRoles())],
            'documentMetadata.*.document_status' => ['nullable', 'string', 'in:'.implode(',', RFI::allowedDocumentStatuses())],
            'documentMetadata.*.revision' => ['nullable', 'string', 'max:40'],
            'documentMetadata.*.discipline' => ['nullable', 'string', 'max:60'],
        ]);

        /** @var User $user */
        $user = Auth::user();

        $rfi = $service->create($this->project, $user, [
            'subject' => $this->subject,
            'body' => $this->body,
            'due_date' => $this->dueDate,
        ]);

        $this->syncDocuments($rfi, $this->documentIds);

        $this->reset(['subject', 'body', 'dueDate', 'documentIds', 'documentMetadata']);
        $this->isCreateMode = false;

        // Reload list
        $this->rfis = RFI::query()
            ->with(['requestedBy:id,first_name,last_name'])
            ->where('project_id', $this->project->id)
            ->latest()
            ->limit(20)
            ->get();

        $this->rfiCount = RFI::query()
            ->where('project_id', $this->project->id)
            ->count();
    }

    public function cancelCreate(): void
    {
        $this->reset(['subject', 'body', 'dueDate', 'documentIds', 'documentMetadata']);
        $this->isCreateMode = false;
    }

    public function render()
    {
        return view('rfis::livewire.admin.projects.project-tab', [
            'availableDocuments' => $this->projectDocumentLibrary->listProjectAccessible((string) $this->project->id),
        ]);
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
                    'document_role' => $this->normalizeRole((string) ($metadata['document_role'] ?? RFI::DOCUMENT_ROLE_REFERENCE)),
                    'document_status' => $this->normalizeStatus((string) ($metadata['document_status'] ?? RFI::DOCUMENT_STATUS_ACTIVE)),
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
     * @param  array<int, string>  $selectedDocumentIds
     */
    private function syncDocuments(RFI $rfi, array $selectedDocumentIds): void
    {
        $allowedDocumentIds = $this->projectDocumentLibrary
            ->allowedDocumentIdsForProject((string) $rfi->project_id, $selectedDocumentIds);

        $pivotPayload = collect($allowedDocumentIds)
            ->mapWithKeys(fn (string $documentId): array => [
                $documentId => $this->documentMetadataValuesForDocument($documentId),
            ])
            ->all();

        $rfi->documents()->sync($pivotPayload);
    }

    /**
     * @return array{document_role:string, document_status:string, revision:?string, discipline:?string}
     */
    private function documentMetadataValuesForDocument(string $documentId): array
    {
        $metadata = $this->documentMetadata[$documentId] ?? [];

        return [
            'document_role' => $this->normalizeRole((string) ($metadata['document_role'] ?? RFI::DOCUMENT_ROLE_REFERENCE)),
            'document_status' => $this->normalizeStatus((string) ($metadata['document_status'] ?? RFI::DOCUMENT_STATUS_ACTIVE)),
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
            'document_role' => RFI::DOCUMENT_ROLE_REFERENCE,
            'document_status' => RFI::DOCUMENT_STATUS_ACTIVE,
            'revision' => null,
            'discipline' => null,
        ];
    }

    private function normalizeRole(string $role): string
    {
        return in_array($role, RFI::allowedDocumentRoles(), true)
            ? $role
            : RFI::DOCUMENT_ROLE_REFERENCE;
    }

    private function normalizeStatus(string $status): string
    {
        return in_array($status, RFI::allowedDocumentStatuses(), true)
            ? $status
            : RFI::DOCUMENT_STATUS_ACTIVE;
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
