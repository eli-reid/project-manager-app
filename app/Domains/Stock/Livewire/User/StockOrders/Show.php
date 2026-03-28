<?php

namespace App\Domains\Stock\Livewire\User\StockOrders;

use App\Domains\Stock\Models\StockOrder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Stock Order Details')]
class Show extends Component
{
    use AuthorizesRequests;

    public StockOrder $stockOrder;

    public function mount(StockOrder $stockOrder): void
    {
        $this->authorize('view', $stockOrder);
        $this->stockOrder = $stockOrder->load(['project:id,name,project_number', 'user:id,name', 'items']);
    }

    public function render()
    {
        return view('stock::livewire.user.stock-orders.show');
    }
}
