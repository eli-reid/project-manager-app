<?php

namespace App\Domains\Submittals\Livewire\Submittals;

use App\Domains\Submittals\Models\Submittal;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Submittals')]
class Index extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    #[Url(as: 'status')]
    public string $status = '';

    #[Url(as: 'docRole')]
    public string $documentRole = '';

    #[Url(as: 'docStatus')]
    public string $documentStatus = '';

    #[Url(as: 'docDiscipline')]
    public string $documentDiscipline = '';

    #[Url(as: 'docRevision')]
    public string $documentRevision = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Submittal::class);
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function updatingDocumentRole(): void
    {
        $this->resetPage();
    }

    public function updatingDocumentStatus(): void
    {
        $this->resetPage();
    }

    public function updatingDocumentDiscipline(): void
    {
        $this->resetPage();
    }

    public function updatingDocumentRevision(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = Submittal::query()
            ->with(['project:id,name,project_number', 'submittedBy:id,first_name,last_name'])
            ->latest();

        if ($this->status !== '') {
            $query->where('status', $this->status);
        }

        $hasMetadataFilter = $this->documentRole !== ''
            || $this->documentStatus !== ''
            || trim($this->documentDiscipline) !== ''
            || trim($this->documentRevision) !== '';

        if ($hasMetadataFilter) {
            $query->whereHas('documents', function ($documentQuery): void {
                if ($this->documentRole !== '') {
                    $documentQuery->where('submittal_documents.document_role', $this->documentRole);
                }

                if ($this->documentStatus !== '') {
                    $documentQuery->where('submittal_documents.document_status', $this->documentStatus);
                }

                if (trim($this->documentDiscipline) !== '') {
                    $documentQuery->where('submittal_documents.discipline', 'like', '%'.trim($this->documentDiscipline).'%');
                }

                if (trim($this->documentRevision) !== '') {
                    $documentQuery->where('submittal_documents.revision', 'like', '%'.trim($this->documentRevision).'%');
                }
            });
        }

        return view('submittals::livewire.user.submittals.index', [
            'submittals' => $query->paginate(15),
            'statuses' => [
                Submittal::STATUS_DRAFT => 'Draft',
                Submittal::STATUS_UNDER_REVIEW => 'Under Review',
                Submittal::STATUS_ARCHITECT_REVIEW => 'Architect Review',
                Submittal::STATUS_OWNER_REVIEW => 'Owner Review',
                Submittal::STATUS_APPROVED => 'Approved',
                Submittal::STATUS_REJECTED => 'Rejected',
                Submittal::STATUS_REVISE => 'Revise',
                Submittal::STATUS_DISTRIBUTED => 'Distributed',
                Submittal::STATUS_CANCELLED => 'Cancelled',
            ],
            'documentRoles' => [
                Submittal::DOCUMENT_ROLE_REFERENCE => 'Reference',
                Submittal::DOCUMENT_ROLE_PRIMARY => 'Primary',
                Submittal::DOCUMENT_ROLE_SUPPORTING => 'Supporting',
                Submittal::DOCUMENT_ROLE_COMPLIANCE => 'Compliance',
            ],
            'documentStatuses' => [
                Submittal::DOCUMENT_STATUS_ACTIVE => 'Active',
                Submittal::DOCUMENT_STATUS_DRAFT => 'Draft',
                Submittal::DOCUMENT_STATUS_SUPERSEDED => 'Superseded',
            ],
        ]);
    }
}
