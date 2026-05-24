<?php

namespace App\Domains\Stock\Livewire\Admin\StockOrders;

use App\Core\Identity\Models\User;
use App\Domains\Projects\Models\Project;
use App\Domains\Stock\Models\StockOrder;
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

    #[Url(as: 'status')]
    public string $filterStatus = '';

    #[Url(as: 'urgency')]
    public string $filterUrgency = '';

    #[Url(as: 'project')]
    public string $filterProject = '';

    #[Url(as: 'user')]
    public string $filterUser = '';

    public function mount(): void
    {
        $this->authorize('viewAny', StockOrder::class);
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

    public function updatingFilterUser(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = StockOrder::query()
            ->with(['project:id,name,project_number', 'user:id,first_name,last_name'])
            ->withCount('items')
            ->latest();

        if ($this->filterStatus !== '') {
            $query->where('status', $this->filterStatus);
        }

        if ($this->filterUrgency !== '') {
            $query->where('urgency', $this->filterUrgency);
        }

        if ($this->filterProject !== '') {
            $query->where('project_id', $this->filterProject);
        }

        if ($this->filterUser !== '') {
            $query->where('user_id', $this->filterUser);
        }

        $pendingCount = StockOrder::query()->where('status', StockOrder::STATUS_PENDING)->count();
        $highUrgencyCount = StockOrder::query()
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
            'projects' => Project::query()->orderBy('name')->get(['id', 'name']),
            'users' => User::query()->orderBy('first_name')->get(['id', 'first_name', 'last_name']),
        ]);
    }
}
