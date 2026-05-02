<div class="space-y-4">
    <div class="flex items-center justify-between gap-3">
        <p class="text-sm text-zinc-500 dark:text-zinc-400">
            {{ $submittalCount }} {{ Str::plural('submittal', $submittalCount) }} for this project.
        </p>
        @can('create', \App\Domains\Submittals\Models\Submittal::class)
            <a href="{{ route('submittals.create', ['projectId' => $project->id, 'returnTo' => route('admin.projects.show', ['project' => $project, 'tab' => 'submittals'], false)]) }}" class="rounded-lg bg-zinc-900 px-3 py-2 text-sm font-medium text-white hover:bg-zinc-700 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-300">+ New Submittal</a>
        @endcan
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-800/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Type</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Vendor</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Current Reviewer</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Need-By</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Items</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse ($submittals as $submittal)
                        @php
                            $statusColor = match ($submittal->statusValue()) {
                                'under_review', 'architect_review', 'owner_review' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
                                'approved' => 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300',
                                'rejected' => 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
                                'revise' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
                                'distributed' => 'bg-teal-100 text-teal-700 dark:bg-teal-900/40 dark:text-teal-300',
                                'cancelled' => 'bg-zinc-100 text-zinc-400 dark:bg-zinc-800 dark:text-zinc-500',
                                default => 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300',
                            };
                        @endphp
                        <tr>
                            <td class="px-4 py-3 text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $submittal->type }}</td>
                            <td class="px-4 py-3 text-sm text-zinc-700 dark:text-zinc-300">{{ $submittal->vendor ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $statusColor }}">{{ $submittal->statusLabel() }}</span>
                            </td>
                            <td class="px-4 py-3 text-sm text-zinc-700 dark:text-zinc-300">
                                {{ trim(($submittal->currentReviewer?->first_name ?? '').' '.($submittal->currentReviewer?->last_name ?? '')) ?: '—' }}
                            </td>
                            <td class="px-4 py-3 text-sm text-zinc-700 dark:text-zinc-300">{{ $submittal->need_by_date?->format('M j, Y') ?? '—' }}</td>
                            <td class="px-4 py-3 text-right text-sm text-zinc-700 dark:text-zinc-300">{{ $submittal->items_count ?? '—' }}</td>
                            <td class="px-4 py-3 text-right text-sm">
                                <a href="{{ route('admin.submittals.show', $submittal) }}" class="font-medium text-zinc-700 hover:underline dark:text-zinc-200">Review</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center text-sm text-zinc-500 dark:text-zinc-400">No submittals linked to this project yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
