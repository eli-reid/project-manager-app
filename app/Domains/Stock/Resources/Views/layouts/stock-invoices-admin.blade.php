<x-layouts::app :title="$title ?? null">
    <x-slot:domainNavbar>
        <div class="mx-auto w-full max-w-7xl">
            <flux:navbar class="flex flex-wrap items-center gap-2">
                @can('viewAny', \App\Domains\Stock\Models\StockOrder::class)
                    <flux:navbar.item
                        :href="route('admin.stock-orders.index')"
                        :current="request()->routeIs('admin.stock-orders.*')"
                        wire:navigate
                    >
                        {{ __('Stock Orders') }}
                    </flux:navbar.item>
                @endcan

                @can('viewAny', \App\Domains\Stock\Models\StockOrderTemplate::class)
                    <flux:navbar.item
                        :href="route('admin.stock-order-templates.index')"
                        :current="request()->routeIs('admin.stock-order-templates.*')"
                        wire:navigate
                    >
                        {{ __('Templates') }}
                    </flux:navbar.item>
                @endcan

                @can('viewAny', \App\Domains\Invoices\Models\Invoice::class)
                    <flux:navbar.item
                        :href="route('admin.invoices.index')"
                        :current="request()->routeIs('admin.invoices.*')"
                        wire:navigate
                    >
                        {{ __('Invoices') }}
                    </flux:navbar.item>
                @endcan
            </flux:navbar>
        </div>
    </x-slot:domainNavbar>

    {{ $slot }}
</x-layouts::app>