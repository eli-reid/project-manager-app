<?php

namespace App\Domains\ChangeOrders\Livewire\Admin\ChangeOrders;

use App\Domains\ChangeOrders\Models\ChangeOrder;
use App\Domains\Projects\Models\Project;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public ?Project $project = null;

    public bool $embedded = false;

    public string $mode = '';

    public string $changeOrderId = '';

    public function mount(?Project $project = null, bool $embedded = false, string $mode = '', string $changeOrderId = ''): void
    {
        $this->authorize('viewAny', ChangeOrder::class);

        $this->project = $project;
        $this->embedded = $embedded && $project instanceof Project;
        $this->mode = $mode;
        $this->changeOrderId = $changeOrderId;
    }

    /**
     * Render the component.
     */
    public function render()
    {
        $query = ChangeOrder::query()
            ->with(['project:id,name,project_number', 'requestedBy:id,first_name,last_name'])
            ->latest();

        if ($this->embedded && $this->project instanceof Project) {
            $query->where('project_id', (string) $this->project->id);
        }

        $isCreateMode = $this->embedded && $this->mode === 'create';
        $isReviewMode = $this->embedded && $this->mode === 'review' && $this->changeOrderId !== '';

        $projectChangeOrdersUrl = $this->embedded && $this->project instanceof Project
            ? route('admin.projects.show', ['project' => $this->project, 'tab' => 'change-orders'])
            : route('admin.change-orders.index');

        $reviewChangeOrder = null;
        $editChangeOrder = null;
        if ($isReviewMode) {
            $reviewChangeOrder = ChangeOrder::query()->find($this->changeOrderId);

            if ($reviewChangeOrder instanceof ChangeOrder) {
                $this->authorize('view', $reviewChangeOrder);
            } else {
                $isReviewMode = false;
            }
        }

        if ($isCreateMode && $this->changeOrderId !== '') {
            $editChangeOrder = ChangeOrder::query()->find($this->changeOrderId);

            if ($editChangeOrder instanceof ChangeOrder) {
                $this->authorize('update', $editChangeOrder);
            } else {
                $editChangeOrder = null;
            }
        }

        return view('change-orders::livewire.admin.change-orders.index', [
            'changeOrders' => $query->paginate(15),
            'embeddedProject' => $this->embedded ? $this->project : null,
            'isCreateMode' => $isCreateMode,
            'isReviewMode' => $isReviewMode,
            'reviewChangeOrder' => $reviewChangeOrder,
            'editChangeOrder' => $editChangeOrder,
            'projectChangeOrdersUrl' => $projectChangeOrdersUrl,
            'changeOrderCreateUrl' => $this->embedded && $this->project instanceof Project
                ? route('admin.projects.show', ['project' => $this->project, 'tab' => 'change-orders', 'changeOrderMode' => 'create'])
                : route('admin.change-orders.create'),
            'changeOrderCount' => $this->embedded && $this->project instanceof Project
                ? ChangeOrder::query()->where('project_id', (string) $this->project->id)->count()
                : null,
        ]);
    }
}
