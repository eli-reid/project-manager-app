<?php

namespace App\Domains\ChangeOrders\Livewire\User\ChangeOrders;

use App\Domains\ChangeOrders\Models\ChangeOrder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class Show extends Component
{
    use AuthorizesRequests;

    public ChangeOrder $changeOrder;

    /**
     * Mount the component.
     */
    public function mount(ChangeOrder $changeOrder): void
    {
        $this->authorize('view', $changeOrder);
        $this->changeOrder = $changeOrder;
    }

    /**
     * Render the component.
     */
    public function render()
    {
        return view('change-orders::livewire.user.change-orders.show');
    }
}
