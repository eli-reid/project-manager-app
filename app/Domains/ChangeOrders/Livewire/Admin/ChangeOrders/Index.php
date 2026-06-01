<?php

namespace App\Domains\ChangeOrders\Livewire\Admin\ChangeOrders;

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
        return view('change-orders::livewire.admin.change-orders.index', [
            'changeOrders' => ChangeOrder::query()
                ->with(['project:id,name,project_number', 'requestedBy:id,first_name,last_name'])
                ->latest()
                ->paginate(15),
        ]);
    }
}
