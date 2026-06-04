<div class="space-y-6">
    @if ($embeddedProject)
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Change Orders</h2>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">
                    {{ $changeOrderCount }} {{ Str::plural('change order', $changeOrderCount) }} on this project.
                </p>
            </div>

            @can('create', \App\Domains\ChangeOrders\Models\ChangeOrder::class)
                <a href="{{ $changeOrderCreateUrl }}" wire:navigate class="rounded-md bg-zinc-900 px-3 py-2 text-xs font-semibold uppercase tracking-wide text-white hover:bg-zinc-700 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-300">
                    + New Change Order
                </a>
            @endcan
        </div>
    @else
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
    @endif

    @if ($isCreateMode && $embeddedProject)
        <livewire:change-orders::admin.change-orders.form :change-order="$editChangeOrder" :project_id="$embeddedProject->id" :embedded="true" :returnTo="$projectChangeOrdersUrl" :key="'project-change-order-create-'.$embeddedProject->id.(($editChangeOrder?->id) ? '-'.$editChangeOrder->id : '')" />
    @elseif ($isReviewMode && $reviewChangeOrder instanceof \App\Domains\ChangeOrders\Models\ChangeOrder)
        <livewire:change-orders::admin.change-orders.show :changeOrder="$reviewChangeOrder" :embedded="true" :returnTo="$projectChangeOrdersUrl" :key="'project-change-order-review-'.$embeddedProject->id.'-'.$reviewChangeOrder->id" />
    @else

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-800/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Title</th>
                        @unless ($embeddedProject)
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Project</th>
                        @endunless
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Status</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Total</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse ($changeOrders as $changeOrder)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/40">
                            <td class="px-4 py-3 text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $changeOrder->title }}</td>
                            @unless ($embeddedProject)
                                <td class="px-4 py-3 text-sm text-zinc-600 dark:text-zinc-300">{{ $changeOrder->project?->name ?? '—' }}</td>
                            @endunless
                            <td class="px-4 py-3 text-xs text-zinc-600 dark:text-zinc-300">{{ ucfirst($changeOrder->status) }}</td>
                            <td class="px-4 py-3 text-right text-sm text-zinc-700 dark:text-zinc-200">${{ number_format((float) $changeOrder->total_amount, 2) }}</td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ $embeddedProject ? route('admin.projects.show', ['project' => $embeddedProject, 'tab' => 'change-orders', 'changeOrderMode' => 'review', 'changeOrderId' => $changeOrder->id]) : route('admin.change-orders.show', $changeOrder) }}" wire:navigate class="rounded-md border border-zinc-300 px-2.5 py-1.5 text-xs font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">Review</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $embeddedProject ? 4 : 5 }}" class="px-4 py-10 text-center text-sm text-zinc-500 dark:text-zinc-400">No change orders found.</td>
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
    @endif
</div>
