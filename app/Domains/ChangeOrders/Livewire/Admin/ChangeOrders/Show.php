<?php

namespace App\Domains\ChangeOrders\Livewire\Admin\ChangeOrders;

use App\Domains\ChangeOrders\Models\ChangeOrder;
use Livewire\Component;

class Show extends Component
{
    public ChangeOrder $changeOrder;

    /**
     * Mount the component.
     */
    public function mount(ChangeOrder $changeOrder)
    {
        $this->changeOrder = $changeOrder;
    }

    /**
     * Render the component.
     */
    public function render()
    {
        return view('livewire.admin.change-orders.show');
    }
}
