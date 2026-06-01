<div class="space-y-4">
    <div class="flex items-center justify-between gap-4">
        <p class="text-sm text-zinc-500 dark:text-zinc-400">
            {{ $stockOrderCount }} stock {{ Str::plural('order', $stockOrderCount) }} linked to this project.
        </p>
        @can('viewAny', \App\Domains\Stock\Models\StockOrder::class)
            <a href="{{ $projectStockUrl }}" wire:navigate class="rounded-md border border-zinc-300 px-3 py-1.5 text-xs font-semibold text-zinc-700 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">
                View in Queue
            </a>
        @endcan
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-800/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Requester</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Urgency</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Items</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">PO #</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Date</th>
                        <th class="relative px-4 py-3"><span class="sr-only">View</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse ($stockOrders as $order)
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
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/40">
                            <td class="px-4 py-3 text-sm text-zinc-700 dark:text-zinc-300">{{ $order->user?->first_name }} {{ $order->user?->last_name }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-md px-2 py-1 text-xs font-semibold {{ $statusColors[$order->status] ?? '' }}">{{ ucfirst($order->status) }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-md px-2 py-1 text-xs font-semibold {{ $urgencyColors[$order->urgency] ?? '' }}">{{ ucfirst($order->urgency) }}</span>
                            </td>
                            <td class="px-4 py-3 text-sm text-zinc-700 dark:text-zinc-300">{{ $order->items_count }}</td>
                            <td class="px-4 py-3 text-sm text-zinc-500 dark:text-zinc-400">{{ $order->po_number ?? '—' }}</td>
                            <td class="px-4 py-3 text-sm text-zinc-500 dark:text-zinc-400">{{ $order->created_at->format('M j, Y') }}</td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('admin.projects.show', ['project' => $project, 'tab' => 'stock', 'stockOrderId' => $order->id]) }}" wire:navigate class="text-xs font-semibold text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center text-sm text-zinc-500 dark:text-zinc-400">No stock orders for this project.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>