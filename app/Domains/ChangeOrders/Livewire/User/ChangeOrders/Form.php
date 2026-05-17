<?php

namespace App\Domains\ChangeOrders\Livewire\User\ChangeOrders;

use App\Domains\ChangeOrders\Models\ChangeOrder;
use Livewire\Component;

class Form extends Component
{
    public ?ChangeOrder $changeOrder = null;

    /**
     * Mount the component.
     */
    public function mount(?ChangeOrder $changeOrder = null)
    {
        $this->changeOrder = $changeOrder;
    }

    /**
     * Render the component.
     */
    public function render()
    {
        return view('livewire.user.change-orders.form');
    }
}
