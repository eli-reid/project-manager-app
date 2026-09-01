<?php

namespace App\Domains\ChangeOrders\Livewire\User\ChangeOrders;

use App\Domains\ChangeOrders\Models\ChangeOrder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class Form extends Component
{
    use AuthorizesRequests;

    public ?ChangeOrder $changeOrder = null;

    /**
     * Mount the component.
     */
    public function mount(?ChangeOrder $changeOrder = null): void
    {
        $this->changeOrder = $changeOrder;

        if ($changeOrder instanceof ChangeOrder && $changeOrder->exists) {
            $this->authorize('update', $changeOrder);

            return;
        }

        $this->authorize('create', ChangeOrder::class);
    }

    /**
     * Render the component.
     */
    public function render()
    {
        return view('change-orders::livewire.user.change-orders.form');
    }
}
