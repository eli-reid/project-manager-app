<?php

namespace App\Domains\Stock\Livewire\Admin\StockOrders;

use App\Core\Identity\Models\User;
use App\Domains\Accounting\Models\AccountingCode;
use App\Domains\Projects\Models\Project;
use App\Domains\Stock\Models\StockOrder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('stock::livewire.layouts.stock-invoices-admin')]
#[Title('Stock Orders Queue')]
class Index extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public ?Project $project = null;

    public bool $embedded = false;

    #[Url(as: 'status')]
    public string $filterStatus = '';

    #[Url(as: 'urgency')]
    public string $filterUrgency = '';

    #[Url(as: 'project')]
    public string $filterProject = '';

    #[Url(as: 'accounting')]
    public string $filterAccountingCode = '';

    #[Url(as: 'user')]
    public string $filterUser = '';

    public function mount(?Project $project = null, bool $embedded = false): void
    {
        $this->authorize('viewAny', StockOrder::class);

        $this->project = $project;
        $this->embedded = $embedded && $project instanceof Project;

        if ($this->embedded) {
            $this->filterProject = (string) $project->id;
        }
    }

    public function updatingFilterStatus(): void
    {
        $this->resetPage();
    }

    public function updatingFilterUrgency(): void
    {
        $this->resetPage();
    }

    public function updatingFilterProject(): void
    {
        $this->resetPage();
    }

    public function updatingFilterAccountingCode(): void
    {
        $this->resetPage();
    }

    public function updatingFilterUser(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = StockOrder::query()
            ->with(['project:id,name,project_number', 'accountingCode:id,code', 'user:id,first_name,last_name'])
            ->withCount('items')
            ->latest();

        $filterProject = $this->embedded && $this->project instanceof Project
            ? (string) $this->project->id
            : $this->filterProject;

        if ($this->filterStatus !== '') {
            $query->where('status', $this->filterStatus);
        }

        if ($this->filterUrgency !== '') {
            $query->where('urgency', $this->filterUrgency);
        }

        if ($filterProject !== '') {
            $query->where('project_id', $filterProject);
        }

        if ($this->filterAccountingCode !== '') {
            $query->where('accounting_code_id', $this->filterAccountingCode);
        }

        if ($this->filterUser !== '') {
            $query->where('user_id', $this->filterUser);
        }

        $summaryQuery = StockOrder::query()
            ->when($filterProject !== '', fn (Builder $query) => $query->where('project_id', $filterProject));

        $pendingCount = (clone $summaryQuery)
            ->where('status', StockOrder::STATUS_PENDING)
            ->count();

        $highUrgencyCount = (clone $summaryQuery)
            ->whereIn('status', [StockOrder::STATUS_PENDING, StockOrder::STATUS_APPROVED])
            ->where('urgency', StockOrder::URGENCY_HIGH)
            ->count();

        return view('stock::livewire.admin.stock-orders.index', [
            'orders' => $query->paginate(20),
            'pendingCount' => $pendingCount,
            'highUrgencyCount' => $highUrgencyCount,
            'statuses' => [
                StockOrder::STATUS_PENDING => 'Pending',
                StockOrder::STATUS_APPROVED => 'Approved',
                StockOrder::STATUS_ORDERED => 'Ordered',
                StockOrder::STATUS_RECEIVED => 'Received',
                StockOrder::STATUS_CANCELLED => 'Cancelled',
            ],
            'urgencies' => [
                StockOrder::URGENCY_LOW => 'Low',
                StockOrder::URGENCY_MEDIUM => 'Medium',
                StockOrder::URGENCY_HIGH => 'High',
            ],
            'projects' => $this->embedded
                ? collect()
                : Project::query()->orderBy('name')->get(['id', 'name']),
            'accountingCodes' => AccountingCode::query()
                ->where('is_active', true)
                ->orderBy('code')
                ->get(['id', 'code']),
            'users' => User::query()->orderBy('first_name')->get(['id', 'first_name', 'last_name']),
            'embeddedProject' => $this->embedded ? $this->project : null,
        ]);
    }
}
