<div class="space-y-6">
    <div class="flex items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">Admin Change Orders</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Review and manage project change orders.</p>
        </div>

        @can('create', \App\Domains\ChangeOrders\Models\ChangeOrder::class)
            <a href="{{ route('admin.change-orders.create') }}" class="rounded-md bg-zinc-900 px-3 py-2 text-xs font-semibold uppercase tracking-wide text-white hover:bg-zinc-700 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-300">
                + New Change Order
            </a>
        @endcan
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-800/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Title</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Project</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Status</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Total</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse ($changeOrders as $changeOrder)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/40">
                            <td class="px-4 py-3 text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $changeOrder->title }}</td>
                            <td class="px-4 py-3 text-sm text-zinc-600 dark:text-zinc-300">{{ $changeOrder->project?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-xs text-zinc-600 dark:text-zinc-300">{{ ucfirst($changeOrder->status) }}</td>
                            <td class="px-4 py-3 text-right text-sm text-zinc-700 dark:text-zinc-200">${{ number_format((float) $changeOrder->total_amount, 2) }}</td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('admin.change-orders.show', $changeOrder) }}" class="rounded-md border border-zinc-300 px-2.5 py-1.5 text-xs font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">Review</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center text-sm text-zinc-500 dark:text-zinc-400">No change orders found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($changeOrders->hasPages())
            <div class="border-t border-zinc-200 px-4 py-3 dark:border-zinc-700">
                {{ $changeOrders->links() }}
            </div>
        @endif
    </div>
</div>
