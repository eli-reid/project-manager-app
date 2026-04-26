<div class="mx-auto w-full max-w-5xl space-y-4 px-4 py-6 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between gap-3">
        <div>
            <flux:heading size="xl">{{ $submittal->type }}</flux:heading>
            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">Project: {{ $submittal->project?->name ?? '—' }}</flux:text>
        </div>
        <div class="flex items-center gap-2">
            @can('update', $submittal)
                <a href="{{ route('submittals.edit', $submittal) }}" class="rounded-lg border border-zinc-300 px-3 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">Edit</a>
            @endcan
            @can('submit', $submittal)
                <flux:button wire:click="submit" variant="primary">Submit for Review</flux:button>
            @endcan
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-3">
        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Status</p>
            <p class="mt-2 text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $submittal->statusLabel() }}</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Spec Reference</p>
            <p class="mt-2 text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $submittal->spec_reference ?? '—' }}</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Need-By Date</p>
            <p class="mt-2 text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $submittal->need_by_date?->format('M j, Y') ?? '—' }}</p>
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="border-b border-zinc-200 px-4 py-3 dark:border-zinc-700">
            <p class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Submittal Items</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-800/50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Description</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Manufacturer</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Model</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Part #</th>
                        <th class="px-4 py-2 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Qty</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Unit</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse ($submittal->items as $item)
                        <tr>
                            <td class="px-4 py-3 text-sm text-zinc-900 dark:text-zinc-100">{{ $item->description }}</td>
                            <td class="px-4 py-3 text-sm text-zinc-700 dark:text-zinc-300">{{ $item->manufacturer ?? '—' }}</td>
                            <td class="px-4 py-3 text-sm text-zinc-700 dark:text-zinc-300">{{ $item->model ?? '—' }}</td>
                            <td class="px-4 py-3 text-sm text-zinc-700 dark:text-zinc-300">{{ $item->part_number ?? '—' }}</td>
                            <td class="px-4 py-3 text-right text-sm text-zinc-700 dark:text-zinc-300">{{ $item->quantity ?? '—' }}</td>
                            <td class="px-4 py-3 text-sm text-zinc-700 dark:text-zinc-300">{{ $item->unit ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-sm text-zinc-500 dark:text-zinc-400">No items added yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="border-b border-zinc-200 px-4 py-3 dark:border-zinc-700">
            <p class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Approval Chain</p>
        </div>
        <div class="divide-y divide-zinc-200 dark:divide-zinc-800">
            @forelse ($submittal->approvals as $approval)
                <div class="flex items-center justify-between px-4 py-3 text-sm">
                    <div>
                        <p class="font-medium text-zinc-900 dark:text-zinc-100">Step {{ $approval->step }}: {{ $approval->reviewer?->first_name }} {{ $approval->reviewer?->last_name }}</p>
                        <p class="text-zinc-500 dark:text-zinc-400">{{ $approval->comments ?? 'No comments yet.' }}</p>
                    </div>
                    <span class="text-zinc-600 dark:text-zinc-300">{{ ucfirst($approval->status) }}</span>
                </div>
            @empty
                <p class="px-4 py-8 text-sm text-zinc-500 dark:text-zinc-400">No reviewers assigned yet.</p>
            @endforelse
        </div>
    </div>

    <div class="rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="border-b border-zinc-200 px-4 py-3 dark:border-zinc-700">
            <p class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Attached Documents</p>
        </div>
        <div class="divide-y divide-zinc-200 dark:divide-zinc-800">
            @forelse ($submittal->documents as $document)
                <div class="px-4 py-3 text-sm text-zinc-700 dark:text-zinc-300">{{ $document->title ?: $document->original_name }}</div>
            @empty
                <p class="px-4 py-8 text-sm text-zinc-500 dark:text-zinc-400">No documents attached.</p>
            @endforelse
        </div>
    </div>
</div>
