<div class="w-full space-y-6">
    @php
        $statusColors = [
            'pending' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
            'approved' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
            'ordered' => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300',
            'received' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
            'cancelled' => 'bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400',
        ];
        $urgencyColors = [
            'low' => 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400',
            'medium' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
            'high' => 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
        ];
    @endphp

    <div class="flex items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-zinc-900 dark:text-zinc-100">
                Review Order{{ $stockOrder->po_number ? ': '.$stockOrder->po_number : '' }}
            </h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">
                Submitted by
                <span class="font-medium text-zinc-700 dark:text-zinc-300">{{ $stockOrder->user?->first_name }} {{ $stockOrder->user?->last_name }}</span>
                on {{ $stockOrder->created_at->format('M j, Y') }}
            </p>
        </div>
        <a href="{{ route('admin.stock-orders.index') }}" wire:navigate class="rounded-md border border-zinc-300 px-3 py-2 text-xs font-semibold uppercase tracking-wide text-zinc-700 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">Back</a>
    </div>

    @if (session('success'))
        <div class="rounded-lg border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-700 dark:bg-red-900/30 dark:text-red-300">{{ session('error') }}</div>
    @endif

    {{-- Status + Meta --}}
    <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="flex flex-wrap items-center gap-4">
            <div>
                <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-zinc-400 dark:text-zinc-500">Status</p>
                <span class="inline-flex rounded-md px-2.5 py-1 text-xs font-semibold {{ $statusColors[$stockOrder->status] ?? '' }}">
                    {{ ucfirst($stockOrder->status) }}
                </span>
            </div>
            <div>
                <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-zinc-400 dark:text-zinc-500">Urgency</p>
                <span class="inline-flex rounded-md px-2.5 py-1 text-xs font-semibold {{ $urgencyColors[$stockOrder->urgency] ?? '' }}">
                    {{ ucfirst($stockOrder->urgency) }}
                </span>
            </div>
            @if ($stockOrder->project)
                <div>
                    <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-zinc-400 dark:text-zinc-500">Project</p>
                    <p class="text-sm text-zinc-700 dark:text-zinc-300">{{ $stockOrder->project->name }}</p>
                </div>
            @endif
            @if ($stockOrder->po_number)
                <div>
                    <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-zinc-400 dark:text-zinc-500">PO #</p>
                    <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $stockOrder->po_number }}</p>
                </div>
            @endif
            @if ($stockOrder->user?->email)
                <div>
                    <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-zinc-400 dark:text-zinc-500">Email</p>
                    <p class="text-sm text-zinc-700 dark:text-zinc-300">{{ $stockOrder->user->email }}</p>
                </div>
            @endif
        </div>
    </div>

    {{-- Processing Actions --}}
    @can('process', $stockOrder)
        <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Process Order</h2>
            <div class="flex flex-wrap gap-2">
                @if ($stockOrder->canTransitionTo(\App\Domains\Stock\Models\StockOrder::STATUS_APPROVED))
                    <button
                        wire:click="processOrder('approved')"
                        wire:confirm="Approve this stock order?"
                        class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-500 disabled:opacity-50"
                    >
                        Approve
                    </button>
                @endif

                @if ($stockOrder->canTransitionTo(\App\Domains\Stock\Models\StockOrder::STATUS_ORDERED))
                    <button
                        wire:click="processOrder('ordered')"
                        wire:confirm="Mark this order as placed/ordered?"
                        class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-50"
                    >
                        Mark Ordered
                    </button>
                @endif

                @if ($stockOrder->canTransitionTo(\App\Domains\Stock\Models\StockOrder::STATUS_RECEIVED))
                    <button
                        wire:click="processOrder('received')"
                        wire:confirm="Mark this order as received?"
                        class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500 disabled:opacity-50"
                    >
                        Mark Received
                    </button>
                @endif

                @if ($stockOrder->canTransitionTo(\App\Domains\Stock\Models\StockOrder::STATUS_CANCELLED))
                    <button
                        wire:click="processOrder('cancelled')"
                        wire:confirm="Cancel this stock order? This cannot be undone."
                        class="rounded-md border border-red-300 px-4 py-2 text-sm font-semibold text-red-600 hover:bg-red-50 dark:border-red-700 dark:text-red-400 dark:hover:bg-red-900/20"
                    >
                        Cancel Order
                    </button>
                @endif

                @if (! $stockOrder->canTransitionTo(\App\Domains\Stock\Models\StockOrder::STATUS_APPROVED) && ! $stockOrder->canTransitionTo(\App\Domains\Stock\Models\StockOrder::STATUS_ORDERED) && ! $stockOrder->canTransitionTo(\App\Domains\Stock\Models\StockOrder::STATUS_RECEIVED) && ! $stockOrder->canTransitionTo(\App\Domains\Stock\Models\StockOrder::STATUS_CANCELLED))
                    <p class="text-sm text-zinc-400 dark:text-zinc-500">No further actions available for this order.</p>
                @endif
            </div>
        </div>
    @endcan

    {{-- Items --}}
    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Items ({{ $stockOrder->items->count() }})</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-800/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Item</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Qty</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Notes</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse ($stockOrder->items as $item)
                        <tr wire:key="item-{{ $item->id }}">
                            <td class="px-4 py-3 text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $item->item_name }}</td>
                            <td class="px-4 py-3 text-sm text-zinc-700 dark:text-zinc-300">{{ $item->quantity }}</td>
                            <td class="px-4 py-3 text-sm">
                                <span class="inline-flex rounded-md bg-zinc-100 px-2 py-1 text-xs font-semibold text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">{{ ucfirst($item->status) }}</span>
                            </td>
                            <td class="px-4 py-3 text-sm text-zinc-500 dark:text-zinc-400">{{ $item->notes ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-8 text-center text-sm text-zinc-500 dark:text-zinc-400">No items listed.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($stockOrder->notes)
        <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <h2 class="mb-2 text-sm font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Notes</h2>
            <p class="text-sm text-zinc-700 dark:text-zinc-300">{{ $stockOrder->notes }}</p>
        </div>
    @endif
</div>
