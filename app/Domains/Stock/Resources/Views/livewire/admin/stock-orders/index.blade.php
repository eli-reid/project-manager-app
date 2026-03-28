<div class="mx-auto w-full max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-zinc-900 dark:text-zinc-100">Stock Orders Queue</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Review, approve, and track all material requests.</p>
        </div>
        @can('viewAny', \App\Domains\Stock\Models\StockOrderTemplate::class)
            <a href="{{ route('admin.stock-order-templates.index') }}" wire:navigate class="rounded-md border border-zinc-300 px-3 py-2 text-xs font-semibold uppercase tracking-wide text-zinc-700 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">Templates</a>
        @endcan
    </div>

    {{-- Stats --}}
    @if ($pendingCount > 0 || $highUrgencyCount > 0)
        <div class="flex flex-wrap gap-3">
            @if ($pendingCount > 0)
                <button wire:click="$set('filterStatus', 'pending')" class="inline-flex items-center gap-2 rounded-lg border border-amber-300 bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-700 hover:bg-amber-100 dark:border-amber-700 dark:bg-amber-900/20 dark:text-amber-300 dark:hover:bg-amber-900/40">
                    <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-amber-200 text-xs font-bold text-amber-800 dark:bg-amber-800 dark:text-amber-200">{{ $pendingCount }}</span>
                    Awaiting Review
                </button>
            @endif
            @if ($highUrgencyCount > 0)
                <button wire:click="$set('filterUrgency', 'high'); $set('filterStatus', '')" class="inline-flex items-center gap-2 rounded-lg border border-red-300 bg-red-50 px-3 py-2 text-xs font-semibold text-red-700 hover:bg-red-100 dark:border-red-700 dark:bg-red-900/20 dark:text-red-300 dark:hover:bg-red-900/40">
                    <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-red-200 text-xs font-bold text-red-800 dark:bg-red-800 dark:text-red-200">{{ $highUrgencyCount }}</span>
                    High Urgency Active
                </button>
            @endif
        </div>
    @endif

    @if (session('success'))
        <div class="rounded-lg border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-700 dark:bg-red-900/30 dark:text-red-300">{{ session('error') }}</div>
    @endif

    {{-- Filters --}}
    <div class="flex flex-wrap items-center gap-3">
        <select wire:model.live="filterStatus" class="rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100">
            <option value="">All Statuses</option>
            @foreach ($statuses as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </select>

        <select wire:model.live="filterUrgency" class="rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100">
            <option value="">All Urgencies</option>
            @foreach ($urgencies as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </select>

        <select wire:model.live="filterProject" class="rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100">
            <option value="">All Projects</option>
            @foreach ($projects as $project)
                <option value="{{ $project->id }}">{{ $project->name }}</option>
            @endforeach
        </select>

        <select wire:model.live="filterUser" class="rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100">
            <option value="">All Requesters</option>
            @foreach ($users as $user)
                <option value="{{ $user->id }}">{{ $user->first_name }} {{ $user->last_name }}</option>
            @endforeach
        </select>

        @if ($filterStatus !== '' || $filterUrgency !== '' || $filterProject !== '' || $filterUser !== '')
            <button wire:click="$set('filterStatus', ''); $set('filterUrgency', ''); $set('filterProject', ''); $set('filterUser', '')" class="text-xs text-zinc-500 underline hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200">Clear filters</button>
        @endif
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-800/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Requester</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">PO / Project</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Urgency</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Items</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Submitted</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse ($orders as $order)
                        <tr wire:key="order-{{ $order->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-800/40">
                            <td class="px-4 py-3 align-top text-sm">
                                <a href="{{ route('admin.stock-orders.show', $order) }}" wire:navigate class="font-medium text-zinc-900 hover:text-zinc-700 dark:text-zinc-100 dark:hover:text-zinc-300">
                                    {{ $order->user?->first_name }} {{ $order->user?->last_name }}
                                </a>
                            </td>
                            <td class="px-4 py-3 align-top text-sm text-zinc-700 dark:text-zinc-300">
                                @if ($order->po_number)
                                    <div class="font-medium">{{ $order->po_number }}</div>
                                @endif
                                <div class="text-xs text-zinc-400 dark:text-zinc-500">{{ $order->project?->name ?? '— No project' }}</div>
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
                                @endphp
                                <span class="inline-flex rounded-md px-2 py-1 text-xs font-semibold {{ $statusColors[$order->status] ?? 'bg-zinc-100 text-zinc-700' }}">
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
                                @endphp
                                <span class="inline-flex rounded-md px-2 py-1 text-xs font-semibold {{ $urgencyColors[$order->urgency] ?? 'bg-zinc-100 text-zinc-600' }}">
                                    {{ ucfirst($order->urgency) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 align-top text-sm text-zinc-700 dark:text-zinc-300">{{ $order->items_count }}</td>
                            <td class="px-4 py-3 align-top text-sm text-zinc-500 dark:text-zinc-400">{{ $order->created_at->format('M j, Y') }}</td>
                            <td class="px-4 py-3 align-top">
                                <x-ui.row-actions-dropdown label="Order actions" width="w-36" :menu-height="100">
                                    <a href="{{ route('admin.stock-orders.show', $order) }}" wire:navigate class="block px-3 py-2 text-left text-sm text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800" @click="closeMenu()">Review</a>
                                </x-ui.row-actions-dropdown>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center text-sm text-zinc-500 dark:text-zinc-400">No stock orders found.</td>
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
