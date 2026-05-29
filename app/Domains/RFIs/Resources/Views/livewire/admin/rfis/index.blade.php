<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">RFIs</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">All requests for information.</p>
        </div>

        <div class="flex items-center gap-2">
            <select
                wire:model.live="status"
                class="rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200"
            >
                <option value="">All Statuses</option>
                @foreach ($statuses as $statusOption)
                    <option value="{{ $statusOption }}">{{ ucfirst($statusOption) }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-800/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">#</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Subject</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Project</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Submitted By</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Due</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse ($rfis as $rfi)
                        @php
                            $statusColor = match ($rfi->status) {
                                'answered' => 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300',
                                'submitted' => 'bg-sky-100 text-sky-700 dark:bg-sky-900/40 dark:text-sky-300',
                                'closed'    => 'bg-violet-100 text-violet-700 dark:bg-violet-900/40 dark:text-violet-300',
                                'cancelled' => 'bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400',
                                default     => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-300',
                            };
                        @endphp
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/40">
                            <td class="px-4 py-3 text-sm text-zinc-500 dark:text-zinc-400">{{ $rfi->number }}</td>
                            <td class="px-4 py-3 text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $rfi->subject }}</td>
                            <td class="px-4 py-3 text-sm text-zinc-700 dark:text-zinc-300">
                                {{ $rfi->project?->name ?? '—' }}
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $statusColor }}">
                                    {{ ucfirst($rfi->status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-zinc-500 dark:text-zinc-400">
                                {{ $rfi->requestedBy?->full_name ?? '—' }}
                            </td>
                            <td class="px-4 py-3 text-xs text-zinc-500 dark:text-zinc-400">
                                {{ $rfi->due_date?->format('M j, Y') ?? '—' }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('admin.rfis.show', $rfi) }}" class="rounded-md border border-zinc-300 px-2.5 py-1.5 text-xs font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">
                                    Review
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center text-sm text-zinc-500 dark:text-zinc-400">
                                No RFIs found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($rfis->hasPages())
            <div class="border-t border-zinc-200 px-4 py-3 dark:border-zinc-700">
                {{ $rfis->links() }}
            </div>
        @endif
    </div>
</div>
