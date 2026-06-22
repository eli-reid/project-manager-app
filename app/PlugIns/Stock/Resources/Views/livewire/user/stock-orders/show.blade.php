<div class="mx-auto w-full max-w-4xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
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
                Stock Order{{ $stockOrder->po_number ? ': '.$stockOrder->po_number : '' }}
            </h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Submitted {{ $stockOrder->created_at->format('M j, Y') }}</p>
        </div>
        <div class="flex items-center gap-2">
            @can('update', $stockOrder)
                <a href="{{ route('stock-orders.edit', $stockOrder) }}" wire:navigate class="rounded-md border border-zinc-300 px-3 py-2 text-xs font-semibold uppercase tracking-wide text-zinc-700 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">Edit</a>
            @endcan
            <a href="{{ route('stock-orders.index') }}" wire:navigate class="rounded-md border border-zinc-300 px-3 py-2 text-xs font-semibold uppercase tracking-wide text-zinc-700 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">Back</a>
        </div>
    </div>

    @if (session('success'))
        <div class="rounded-lg border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">{{ session('success') }}</div>
    @endif

    {{-- Status Banner --}}
    <div class="flex flex-wrap items-center gap-3 rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="flex items-center gap-2">
            <span class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Status</span>
            <span class="inline-flex rounded-md px-2.5 py-1 text-xs font-semibold {{ $statusColors[$stockOrder->status] ?? 'bg-zinc-100 text-zinc-700' }}">
                {{ ucfirst($stockOrder->status) }}
            </span>
        </div>
        <div class="flex items-center gap-2">
            <span class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Urgency</span>
            <span class="inline-flex rounded-md px-2.5 py-1 text-xs font-semibold {{ $urgencyColors[$stockOrder->urgency] ?? 'bg-zinc-100 text-zinc-600' }}">
                {{ ucfirst($stockOrder->urgency) }}
            </span>
        </div>
        @if ($stockOrder->project)
            <div class="flex items-center gap-2">
                <span class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Project</span>
                <span class="text-sm text-zinc-700 dark:text-zinc-300">{{ $stockOrder->project->name }}</span>
            </div>
        @endif
        @if ($stockOrder->po_number)
            <div class="flex items-center gap-2">
                <span class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">PO #</span>
                <span class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $stockOrder->po_number }}</span>
            </div>
        @endif
    </div>

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
                            <td colspan="4" class="px-4 py-8 text-center text-sm text-zinc-500 dark:text-zinc-400">No items on this order.</td>
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
