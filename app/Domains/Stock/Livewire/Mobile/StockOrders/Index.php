<?php

namespace App\Domains\Stock\Livewire\Mobile\StockOrders;

use App\Domains\Stock\Models\StockOrder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.mobile')]
#[Title('Stock Orders')]
class Index extends Component
{
    use AuthorizesRequests;

    public function mount(): void
    {
        $this->authorize('viewAny', StockOrder::class);
    }

    public function render()
    {
        return view('stock::livewire.mobile.stock-orders.index');
    }
}
