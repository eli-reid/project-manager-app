<?php

namespace App\Domains\ChangeOrders\Livewire\User\ChangeOrders;

use App\Domains\ChangeOrders\Models\ChangeOrder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public function mount(): void
    {
        $this->authorize('viewAny', ChangeOrder::class);
    }

    /**
     * Render the component.
     */
    public function render()
    {
        return view('change-orders::livewire.user.change-orders.index', [
            'changeOrders' => ChangeOrder::query()->latest()->paginate(15),
        ]);
    }
}
