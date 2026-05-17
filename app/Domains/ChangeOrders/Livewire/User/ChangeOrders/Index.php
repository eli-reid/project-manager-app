<?php

namespace App\Domains\ChangeOrders\Livewire\User\ChangeOrders;

use App\Domains\ChangeOrders\Models\ChangeOrder;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    /**
     * Render the component.
     */
    public function render()
    {
        return view('livewire.user.change-orders.index', [
            'changeOrders' => ChangeOrder::paginate(15),
        ]);
    }
}
