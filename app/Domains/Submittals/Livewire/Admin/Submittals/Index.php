<?php

namespace App\Domains\Submittals\Livewire\Admin\Submittals;

use App\Domains\Projects\Models\Project;
use App\Domains\Projects\Services\ProjectTabLinkBuilder;
use App\Domains\Submittals\Models\Submittal;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('stock::livewire.layouts.stock-invoices-admin')]
#[Title('Submittal Approval Queue')]
class Index extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public ?Project $project = null;

    public bool $embedded = false;

    public string $mode = '';

    public string $submittalId = '';

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

    public function mount(?Project $project = null, bool $embedded = false, string $mode = '', string $submittalId = ''): void
    {
        if (! auth()->user()?->can('viewAny', Submittal::class) && ! auth()->user()?->can('create', Submittal::class)) {
            abort(403);
        }

        $this->project = $project;
        $this->embedded = $embedded && $project instanceof Project;
        $this->mode = $mode;
        $this->submittalId = $submittalId;
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

        if ($this->embedded && $this->project instanceof Project) {
            $query->where('project_id', (string) $this->project->id);
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

        $isCreateMode = $this->embedded && $this->mode === 'create';
        $isReviewMode = $this->embedded && $this->mode === 'review' && $this->submittalId !== '';

        $projectSubmittalsUrl = $this->embedded && $this->project instanceof Project
            ? app(ProjectTabLinkBuilder::class)->to($this->project, 'submittals')
            : route('admin.submittals.index');

        $reviewSubmittal = null;
        if ($isReviewMode) {
            $reviewSubmittal = Submittal::query()->find($this->submittalId);

            if ($reviewSubmittal instanceof Submittal) {
                $this->authorize('view', $reviewSubmittal);
            } else {
                $isReviewMode = false;
            }
        }

        return view('submittals::livewire.admin.submittals.index', [
            'submittals' => $query->paginate(15),
            'embeddedProject' => $this->embedded ? $this->project : null,
            'isCreateMode' => $isCreateMode,
            'isReviewMode' => $isReviewMode,
            'reviewSubmittal' => $reviewSubmittal,
            'projectSubmittalsUrl' => $projectSubmittalsUrl,
            'submittalCreateUrl' => $this->embedded && $this->project instanceof Project
                ? app(ProjectTabLinkBuilder::class)->to($this->project, 'submittals', mode: 'create')
                : route('admin.submittals.index'),
            'submittalCount' => $this->embedded && $this->project instanceof Project
                ? Submittal::query()->where('project_id', (string) $this->project->id)->count()
                : null,
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
