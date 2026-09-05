<?php

namespace App\Domains\RFIs\Livewire\Admin\RFIs;

use App\Core\Identity\Models\User;
use App\Domains\Documents\Contracts\ProjectDocumentLibraryContract;
use App\Domains\Projects\Models\Project;
use App\Domains\RFIs\Models\RFI;
use App\Domains\RFIs\Services\RFILifecycleService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('RFIs')]
class Index extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public ?Project $project = null;

    public bool $embedded = false;

    public bool $isCreateMode = false;

    /**
     * @var array<int, string>
     */
    public array $documentIds = [];

    /**
     * @var array<string, array{document_role:string, document_status:string, revision:?string, discipline:?string}>
     */
    public array $documentMetadata = [];

    public string $subject = '';

    public string $body = '';

    public ?string $dueDate = null;

    private ProjectDocumentLibraryContract $projectDocumentLibrary;

    #[Url(as: 'status')]
    public string $status = '';

    public function boot(ProjectDocumentLibraryContract $projectDocumentLibrary): void
    {
        $this->projectDocumentLibrary = $projectDocumentLibrary;
    }

    public function mount(?Project $project = null, bool $embedded = false, bool $isCreateMode = false): void
    {
        if (! auth()->user()?->can('viewAny', RFI::class) && ! auth()->user()?->can('create', RFI::class)) {
            abort(403);
        }

        $this->project = $project;
        $this->embedded = $embedded && $project instanceof Project;
        $this->isCreateMode = $isCreateMode;
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function submitRfi(RFILifecycleService $service): void
    {
        $this->authorize('create', RFI::class);
        abort_unless($this->project instanceof Project, 422, 'Project context is required.');

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

        $service->submit($rfi);

        $this->syncDocuments($rfi, $this->documentIds);

        $this->reset(['subject', 'body', 'dueDate', 'documentIds', 'documentMetadata']);
        $this->isCreateMode = false;
    }

    public function cancelCreate(): void
    {
        $this->reset(['subject', 'body', 'dueDate', 'documentIds', 'documentMetadata']);
        $this->isCreateMode = false;
    }

    public function render()
    {
        $query = RFI::query()
            ->with(['project:id,name,project_number', 'requestedBy:id,first_name,last_name'])
            ->latest();

        if ($this->embedded && $this->project instanceof Project) {
            $query->where('project_id', (string) $this->project->id);
        }

        if ($this->status !== '') {
            $query->where('status', $this->status);
        }

        return view('rfis::livewire.admin.rfis.index', [
            'rfis' => $query->paginate(15),
            'statuses' => RFI::statuses(),
            'embeddedProject' => $this->embedded ? $this->project : null,
            'rfiCount' => $this->embedded && $this->project instanceof Project
                ? RFI::query()->where('project_id', (string) $this->project->id)->count()
                : null,
            'availableDocuments' => $this->embedded && $this->project instanceof Project
                ? $this->projectDocumentLibrary->listProjectAccessible((string) $this->project->id)
                : collect(),
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
