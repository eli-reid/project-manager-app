<?php

namespace App\Livewire\Layouts;

use Illuminate\View\View;
use Livewire\Component;

class StockInvoicesAdmin extends Component
{
    public function render(): View
    {
        return view('stock::livewire.layouts.stock-invoices-admin');
    }
}
