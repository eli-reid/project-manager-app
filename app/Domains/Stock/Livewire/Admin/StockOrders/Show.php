<?php

namespace App\Domains\Stock\Livewire\Admin\StockOrders;

use App\Domains\Stock\Models\StockOrder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.stock-invoices-admin')]
#[Title('Review Stock Order')]
class Show extends Component
{
    use AuthorizesRequests;

    public StockOrder $stockOrder;

    public function mount(StockOrder $stockOrder): void
    {
        $this->authorize('view', $stockOrder);
        $this->stockOrder = $stockOrder->load(['project:id,name,project_number', 'user:id,first_name,last_name,email', 'items']);
    }

    public function processOrder(string $targetStatus): void
    {
        $this->authorize('process', $this->stockOrder);

        if (! $this->stockOrder->canTransitionTo($targetStatus)) {
            session()->flash('error', 'This status transition is not allowed.');

            return;
        }

        $this->stockOrder->transitionTo($targetStatus);
        $this->stockOrder->refresh()->load(['project:id,name,project_number', 'user:id,first_name,last_name,email', 'items']);

        $labels = [
            StockOrder::STATUS_APPROVED => 'approved',
            StockOrder::STATUS_ORDERED => 'marked as ordered',
            StockOrder::STATUS_RECEIVED => 'marked as received',
            StockOrder::STATUS_CANCELLED => 'cancelled',
        ];

        session()->flash('success', 'Order '.(($labels[$targetStatus]) ?? $targetStatus).' successfully.');
    }

    public function render()
    {
        return view('stock::livewire.admin.stock-orders.show');
    }
}
