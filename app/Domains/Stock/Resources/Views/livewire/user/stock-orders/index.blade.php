<div class="mx-auto w-full max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-zinc-900 dark:text-zinc-100">My Stock Orders</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Track material requests and their fulfillment status.</p>
        </div>
        <div class="flex items-center gap-2">
            @can('viewAny', \App\Domains\Stock\Models\StockOrderTemplate::class)
                <a href="{{ route('stock-orders.templates.browse') }}" wire:navigate class="rounded-md border border-zinc-300 px-3 py-2 text-xs font-semibold uppercase tracking-wide text-zinc-700 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">Templates</a>
            @endcan
            @can('create', \App\Domains\Stock\Models\StockOrder::class)
                <a href="{{ route('stock-orders.create') }}" wire:navigate class="inline-flex items-center rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-700 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-300">New Order</a>
            @endcan
        </div>
    </div>

    {{-- Filters --}}
    <div class="flex flex-wrap items-center gap-3">
        <select wire:model.live="filterStatus" class="rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100">
            <option value="">All Statuses</option>
            @foreach ($statuses as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </select>

        <select wire:model.live="filterUrgency" class="rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100">
            <option value="">All Urgencies</option>
            @foreach ($urgencies as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </select>

        @if ($filterStatus !== '' || $filterUrgency !== '')
            <button wire:click="$set('filterStatus', ''); $set('filterUrgency', '')" class="text-xs text-zinc-500 underline hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200">Clear</button>
        @endif
    </div>

    @if (session('success'))
        <div class="rounded-lg border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">{{ session('success') }}</div>
    @endif

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-800/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Order</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Project</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Urgency</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Items</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Date</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse ($orders as $order)
                        <tr wire:key="order-{{ $order->id }}" class="cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-800/40" 
                        :href="`{{ route('stock-orders.show', $order) }}`" wire:navigate>
                
                            <td class="px-4 py-3 align-top text-sm">
                                <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $order->po_number ?? 'No PO' }}</span>
                            </td>
                            <td class="px-4 py-3 align-top text-sm text-zinc-700 dark:text-zinc-300">
                                {{ $order->project?->name ?? '—' }}
                            </td>
                            <td class="px-4 py-3 align-top text-sm">
                                @php
                                    $statusColors = [
                                        'pending' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
                                        'approved' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
                                        'ordered' => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300',
                                        'received' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
                                        'cancelled' => 'bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400',
                                    ];
                                    $statusColor = $statusColors[$order->status] ?? 'bg-zinc-100 text-zinc-700';
                                @endphp
                                <span class="inline-flex rounded-md px-2 py-1 text-xs font-semibold {{ $statusColor }}">
                                    {{ ucfirst($order->status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 align-top text-sm">
                                @php
                                    $urgencyColors = [
                                        'low' => 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400',
                                        'medium' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
                                        'high' => 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
                                    ];
                                    $urgencyColor = $urgencyColors[$order->urgency] ?? 'bg-zinc-100 text-zinc-600';
                                @endphp
                                <span class="inline-flex rounded-md px-2 py-1 text-xs font-semibold {{ $urgencyColor }}">
                                    {{ ucfirst($order->urgency) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 align-top text-sm text-zinc-700 dark:text-zinc-300">
                                {{ $order->items->count() }}
                            </td>
                            <td class="px-4 py-3 align-top text-sm text-zinc-500 dark:text-zinc-400">
                                {{ $order->created_at->format('M j, Y') }}
                            </td>
                            <td class="px-4 py-3 align-top" onclick="event.stopPropagation()">
                                <x-ui.row-actions-dropdown label="Order actions" width="w-36" :menu-height="120">
                                    <a href="{{ route('stock-orders.show', $order) }}" wire:navigate class="block px-3 py-2 text-left text-sm text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800" @click="closeMenu()">View</a>
                                    @can('update', $order)
                                        <a href="{{ route('stock-orders.edit', $order) }}" wire:navigate class="block px-3 py-2 text-left text-sm text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800" @click="closeMenu()">Edit</a>
                                    @endcan
                                </x-ui.row-actions-dropdown>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center text-sm text-zinc-500 dark:text-zinc-400">
                                No stock orders found.
                                @can('create', \App\Domains\Stock\Models\StockOrder::class)
                                    <a href="{{ route('stock-orders.create') }}" wire:navigate class="ml-1 underline hover:text-zinc-700 dark:hover:text-zinc-200">Create one</a>.
                                @endcan
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-zinc-200 px-4 py-3 dark:border-zinc-700">
            {{ $orders->links() }}
        </div>
    </div>
</div>
