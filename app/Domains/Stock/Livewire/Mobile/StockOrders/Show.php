<?php

namespace App\Domains\Stock\Livewire\Mobile\StockOrders;

use App\Domains\Stock\Livewire\User\StockOrders\Show as DesktopShow;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

#[Layout('layouts.mobile')]
#[Title('Stock Order Details')]
class Show extends DesktopShow
{
    public function render(): View
    {
        return view('stock::livewire.mobile.stock-orders.show');
    }
}
