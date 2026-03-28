<?php

namespace App\Domains\Stock\Livewire\User\StockOrders;

use App\Domains\Stock\Models\StockOrder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('My Stock Orders')]
class Index extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    #[Url(as: 'status')]
    public string $filterStatus = '';

    #[Url(as: 'urgency')]
    public string $filterUrgency = '';

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

    public function render()
    {
        $user = Auth::user();

        $query = StockOrder::query()
            ->with(['project:id,name,project_number', 'items'])
            ->where('user_id', $user->id)
            ->latest();

        if ($this->filterStatus !== '') {
            $query->where('status', $this->filterStatus);
        }

        if ($this->filterUrgency !== '') {
            $query->where('urgency', $this->filterUrgency);
        }

        return view('stock::livewire.user.stock-orders.index', [
            'orders' => $query->paginate(15),
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
