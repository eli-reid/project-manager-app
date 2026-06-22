<?php

namespace App\Domains\Stock\Livewire\Layouts;

use App\Domains\Invoices\Models\Invoice;
use App\Domains\Stock\Models\StockOrder;
use App\Domains\Stock\Models\StockOrderTemplate;
use App\Support\Contracts\ProvidesDomainNavbar;
use Illuminate\View\View;
use Livewire\Component;

class StockInvoicesAdmin extends Component implements ProvidesDomainNavbar
{
    /**
     * @return array<int, array{label: string, href: string, current: bool, visible?: bool}>
     */
    public static function navbarItems(): array
    {
        return array_values(array_filter([
            auth()->user()?->can('viewAny', StockOrder::class)
                ? [
                    'label' => (string) __('Stock Orders'),
                    'href' => route('admin.stock-orders.index'),
                    'current' => request()->routeIs('admin.stock-orders.*'),
                ]
                : null,
            auth()->user()?->can('viewAny', StockOrderTemplate::class)
                ? [
                    'label' => (string) __('Templates'),
                    'href' => route('admin.stock-order-templates.index'),
                    'current' => request()->routeIs('admin.stock-order-templates.*'),
                ]
                : null,
            auth()->user()?->can('viewAny', Invoice::class)
                ? [
                    'label' => (string) __('Invoices'),
                    'href' => route('admin.invoices.index'),
                    'current' => request()->routeIs('admin.invoices.*'),
                ]
                : null,
        ]));
    }

    public function render(): View
    {
        return view('stock::livewire.layouts.stock-invoices-admin');
    }
}
