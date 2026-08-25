<?php

namespace App\Domains\Stock\Livewire\Mobile\StockOrders;

use App\Domains\Stock\Models\StockOrder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.mobile')]
#[Title('Stock Orders')]
class Index extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    #[Url(as: 'search')]
    public string $search = '';

    #[Url(as: 'status')]
    public string $filterStatus = '';

    #[Url(as: 'urgency')]
    public string $filterUrgency = '';

    public function mount(): void
    {
        $this->authorize('viewAny', StockOrder::class);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterStatus(): void
    {
        $this->resetPage();
    }

    public function updatingFilterUrgency(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->filterStatus = '';
        $this->filterUrgency = '';
        $this->resetPage();
    }

    public function render(): View
    {
        $user = Auth::user();
        abort_unless($user !== null, 401);

        $query = StockOrder::query()
            ->with(['project:id,name,project_number'])
            ->withCount('items')
            ->ownedBy((string) $user->id)
            ->latest();

        if (trim($this->search) !== '') {
            $search = trim($this->search);

            $query->where(function ($orderQuery) use ($search): void {
                $orderQuery->where('po_number', 'like', '%'.$search.'%')
                    ->orWhereHas('project', function ($projectQuery) use ($search): void {
                        $projectQuery->where('name', 'like', '%'.$search.'%')
                            ->orWhere('project_number', 'like', '%'.$search.'%');
                    });
            });
        }

        if ($this->filterStatus !== '') {
            $query->where('status', $this->filterStatus);
        }

        if ($this->filterUrgency !== '') {
            $query->where('urgency', $this->filterUrgency);
        }

        return view('stock::livewire.mobile.stock-orders.index', [
            'orders' => $query->paginate(12),
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
        ]);
    }
}
