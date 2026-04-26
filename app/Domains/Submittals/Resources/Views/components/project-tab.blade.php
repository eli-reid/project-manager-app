<div class="space-y-4">
    <div class="flex items-center justify-between gap-3">
        <p class="text-sm text-zinc-500 dark:text-zinc-400">
            {{ $submittalCount }} {{ Str::plural('submittal', $submittalCount) }} for this project.
        </p>
        <a href="{{ route('submittals.create') }}" class="rounded-lg bg-zinc-900 px-3 py-2 text-sm font-medium text-white hover:bg-zinc-700 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-300">Create Submittal</a>
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-800/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Type</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Vendor</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Submitted By</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse ($submittals as $submittal)
                        <tr>
                            <td class="px-4 py-3 text-sm text-zinc-900 dark:text-zinc-100">{{ $submittal->type }}</td>
                            <td class="px-4 py-3 text-sm text-zinc-700 dark:text-zinc-300">{{ $submittal->vendor ?? '—' }}</td>
                            <td class="px-4 py-3 text-sm text-zinc-700 dark:text-zinc-300">{{ $submittal->statusLabel() }}</td>
                            <td class="px-4 py-3 text-sm text-zinc-700 dark:text-zinc-300">{{ trim(($submittal->submittedBy?->first_name ?? '').' '.($submittal->submittedBy?->last_name ?? '')) ?: '—' }}</td>
                            <td class="px-4 py-3 text-right text-sm">
                                <a href="{{ route('submittals.show', $submittal) }}" class="font-medium text-zinc-700 hover:underline dark:text-zinc-200">Open</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center text-sm text-zinc-500 dark:text-zinc-400">No submittals linked to this project yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
