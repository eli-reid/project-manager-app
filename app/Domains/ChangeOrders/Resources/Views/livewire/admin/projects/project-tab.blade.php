<div class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Change Orders</h2>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">
                {{ $changeOrderCount }} {{ Str::plural('change order', $changeOrderCount) }} on this project.
            </p>
        </div>

        @can('create', App\Domains\ChangeOrders\Models\ChangeOrder::class)
            <a
                href="{{ route('admin.projects.show', ['project' => $project, 'tab' => 'change-orders', 'changeOrderMode' => 'create']) }}"
                wire:navigate
                class="rounded-md bg-zinc-900 px-3 py-2 text-xs font-semibold uppercase tracking-wide text-white hover:bg-zinc-700 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-300"
            >
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
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Status</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Docs</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Total</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Submitted</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse ($changeOrders as $changeOrder)
                        @php
                            $statusColor = match ($changeOrder->status) {
                                'approved'    => 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300',
                                'submitted'   => 'bg-sky-100 text-sky-700 dark:bg-sky-900/40 dark:text-sky-300',
                                'implemented' => 'bg-violet-100 text-violet-700 dark:bg-violet-900/40 dark:text-violet-300',
                                'rejected'    => 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
                                'cancelled'   => 'bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400',
                                default       => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-300',
                            };
                        @endphp
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/40">
                            <td class="px-4 py-3 text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                {{ $changeOrder->title }}
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $statusColor }}">
                                    {{ ucfirst($changeOrder->status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right text-xs text-zinc-600 dark:text-zinc-300">
                                {{ (int) ($changeOrder->documents_count ?? 0) }}
                            </td>
                            <td class="px-4 py-3 text-right text-sm text-zinc-700 dark:text-zinc-200">
                                ${{ number_format((float) $changeOrder->total_amount, 2) }}
                            </td>
                            <td class="px-4 py-3 text-xs text-zinc-500 dark:text-zinc-400">
                                {{ $changeOrder->submitted_at?->format('M j, Y') ?? '—' }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                @can('view', $changeOrder)
                                    <a href="{{ route('admin.projects.show', ['project' => $project, 'tab' => 'change-orders', 'changeOrderMode' => 'review', 'changeOrderId' => $changeOrder->id]) }}" wire:navigate class="rounded-md border border-zinc-300 px-2.5 py-1.5 text-xs font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">
                                        Review
                                    </a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-sm text-zinc-500 dark:text-zinc-400">
                                No change orders yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
