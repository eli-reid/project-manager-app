<?php

namespace App\Domains\Stock\Livewire\Mobile\StockOrders;

use App\Domains\Projects\Models\Project;
use App\Domains\Stock\Livewire\User\StockOrders\Form as DesktopForm;
use App\Domains\Stock\Models\StockOrder;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

#[Layout('layouts.mobile')]
#[Title('Stock Order')]
class Form extends DesktopForm
{
    protected function redirectToOrder(StockOrder $stockOrder): void
    {
        $this->redirectRoute('stock-orders.mobile.show', ['stockOrder' => $stockOrder], navigate: true);
    }

    public function render(): View
    {
        return view('stock::livewire.mobile.stock-orders.form', [
            'projects' => Project::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'project_number']),
            'urgencies' => [
                StockOrder::URGENCY_LOW => 'Low',
                StockOrder::URGENCY_MEDIUM => 'Medium',
                StockOrder::URGENCY_HIGH => 'High',
            ],
        ])->title($this->isEdit ? __('Edit Stock Order') : __('New Stock Order'));
    }
}
