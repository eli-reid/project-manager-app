<div wire:poll.5s class="mx-auto w-full max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-zinc-900 dark:text-zinc-100">Queue Manager</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Monitor queued, failed, and completed jobs in real time.</p>
        </div>

        <div class="flex gap-2">
            <button type="button" wire:click="runNextJob" wire:confirm="Run the next queued job now?" class="inline-flex items-center rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-700 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-300">Run Next Job</button>
            <button type="button" wire:click="retryAllFailed" wire:confirm="Retry all failed jobs?" class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-500">Retry All Failed</button>
            <button type="button" wire:click="clearAllFailed" wire:confirm="Clear all failed jobs? This cannot be undone." class="inline-flex items-center rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-500">Clear Failed</button>
        </div>
    </div>

    @if (session('success'))
        <div class="rounded-lg border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">{{ session('success') }}</div>
    @endif

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Pending</div>
            <div class="mt-2 text-2xl font-semibold text-zinc-900 dark:text-zinc-100">{{ $stats['pending'] }}</div>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Running</div>
            <div class="mt-2 text-2xl font-semibold text-zinc-900 dark:text-zinc-100">{{ $stats['running'] }}</div>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Failed</div>
            <div class="mt-2 text-2xl font-semibold text-red-600">{{ $stats['failed'] }}</div>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Completed Today</div>
            <div class="mt-2 text-2xl font-semibold text-emerald-600">{{ $stats['completed_today'] }}</div>
        </div>
    </div>

    <div class="flex flex-wrap gap-2">
        <button type="button" wire:click="setActiveTab('pending')" class="rounded-lg px-3 py-2 text-sm {{ $activeTab === 'pending' ? 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900' : 'bg-zinc-200 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200' }}">Pending Jobs</button>
        <button type="button" wire:click="setActiveTab('failed')" class="rounded-lg px-3 py-2 text-sm {{ $activeTab === 'failed' ? 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900' : 'bg-zinc-200 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200' }}">Failed Jobs</button>
        <button type="button" wire:click="setActiveTab('history')" class="rounded-lg px-3 py-2 text-sm {{ $activeTab === 'history' ? 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900' : 'bg-zinc-200 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200' }}">History</button>
        <button type="button" wire:click="setActiveTab('batches')" class="rounded-lg px-3 py-2 text-sm {{ $activeTab === 'batches' ? 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900' : 'bg-zinc-200 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200' }}">Batches</button>
    </div>

    @if ($activeTab === 'pending')
        <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                    <thead class="bg-zinc-50 dark:bg-zinc-800/50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Job</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Queue</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Attempts</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Created</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                        @forelse ($jobs as $job)
                            <tr>
                                <td class="px-4 py-3 text-sm text-zinc-800 dark:text-zinc-100">{{ $job->decoded_job_class }}</td>
                                <td class="px-4 py-3 text-sm text-zinc-600 dark:text-zinc-300">{{ $job->queue }}</td>
                                <td class="px-4 py-3 text-sm text-zinc-600 dark:text-zinc-300">{{ $job->attempts }}</td>
                                <td class="px-4 py-3 text-sm text-zinc-600 dark:text-zinc-300">{{ \Illuminate\Support\Carbon::createFromTimestamp($job->created_at)->toDateTimeString() }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-10 text-center text-sm text-zinc-500 dark:text-zinc-400">No queued jobs.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-zinc-200 px-4 py-3 dark:border-zinc-700">{{ $jobs->links() }}</div>
        </div>
    @endif

    @if ($activeTab === 'failed')
        <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                    <thead class="bg-zinc-50 dark:bg-zinc-800/50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Job</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Queue</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Failed At</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                        @forelse ($failedJobs as $job)
                            <tr>
                                <td class="px-4 py-3 text-sm text-zinc-800 dark:text-zinc-100">{{ $job->decoded_job_class }}</td>
                                <td class="px-4 py-3 text-sm text-zinc-600 dark:text-zinc-300">{{ $job->queue }}</td>
                                <td class="px-4 py-3 text-sm text-zinc-600 dark:text-zinc-300">{{ $job->failed_at }}</td>
                                <td class="px-4 py-3 text-right">
                                    <button type="button" wire:click="retryJob('{{ $job->uuid }}')" wire:confirm="Retry this failed job?" class="rounded-md bg-blue-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-blue-500">Retry</button>
                                    <button type="button" wire:click="deleteJob('{{ $job->uuid }}')" wire:confirm="Delete this failed job?" class="rounded-md bg-red-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-red-500">Delete</button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-10 text-center text-sm text-zinc-500 dark:text-zinc-400">No failed jobs.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-zinc-200 px-4 py-3 dark:border-zinc-700">{{ $failedJobs->links() }}</div>
        </div>
    @endif

    @if ($activeTab === 'history')
        <div class="space-y-4">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div class="max-w-xs">
                    <label for="history-filter" class="mb-2 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">History Status</label>
                    <select id="history-filter" wire:model.live="historyFilter" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100">
                        <option value="all">All</option>
                        <option value="running">Running</option>
                        <option value="completed">Completed</option>
                        <option value="failed">Failed</option>
                    </select>
                </div>

                <button type="button" wire:click="clearHistory" wire:confirm="Clear {{ $historyFilter === 'all' ? 'all' : $historyFilter }} history records? This cannot be undone." class="inline-flex items-center rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-500">
                    Clear History
                </button>
            </div>

            <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                        <thead class="bg-zinc-50 dark:bg-zinc-800/50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Job</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Started</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Finished</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Duration</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                            @forelse ($history as $item)
                                <tr>
                                    <td class="px-4 py-3 text-sm text-zinc-800 dark:text-zinc-100">{{ $item->job_class }}</td>
                                    <td class="px-4 py-3 text-sm">
                                        <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold {{ $item->status === 'completed' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' : ($item->status === 'failed' ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300' : 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300') }}">{{ ucfirst($item->status) }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-zinc-600 dark:text-zinc-300">{{ $item->started_at?->toDateTimeString() }}</td>
                                    <td class="px-4 py-3 text-sm text-zinc-600 dark:text-zinc-300">{{ $item->finished_at?->toDateTimeString() ?? '-' }}</td>
                                    <td class="px-4 py-3 text-sm text-zinc-600 dark:text-zinc-300">{{ $item->duration_ms ? $item->duration_ms.' ms' : '-' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-4 py-10 text-center text-sm text-zinc-500 dark:text-zinc-400">No history records.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-zinc-200 px-4 py-3 dark:border-zinc-700">{{ $history->links() }}</div>
            </div>
        </div>
    @endif

    @if ($activeTab === 'batches')
        <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                    <thead class="bg-zinc-50 dark:bg-zinc-800/50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Name</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Total</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Pending</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Failed</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Created</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                        @forelse ($batches as $batch)
                            <tr>
                                <td class="px-4 py-3 text-sm text-zinc-800 dark:text-zinc-100">{{ $batch->name }}</td>
                                <td class="px-4 py-3 text-sm text-zinc-600 dark:text-zinc-300">{{ $batch->total_jobs }}</td>
                                <td class="px-4 py-3 text-sm text-zinc-600 dark:text-zinc-300">{{ $batch->pending_jobs }}</td>
                                <td class="px-4 py-3 text-sm text-zinc-600 dark:text-zinc-300">{{ $batch->failed_jobs }}</td>
                                <td class="px-4 py-3 text-sm text-zinc-600 dark:text-zinc-300">{{ \Illuminate\Support\Carbon::createFromTimestamp($batch->created_at)->toDateTimeString() }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-10 text-center text-sm text-zinc-500 dark:text-zinc-400">No job batches found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-zinc-200 px-4 py-3 dark:border-zinc-700">{{ $batches->links() }}</div>
        </div>
    @endif
</div>
