<div class="mx-auto w-full max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    {{-- Header --}}
    <div class="flex items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-zinc-900 dark:text-zinc-100">Queue Monitor</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Monitor and manage background jobs across your application.</p>
        </div>
        <div class="flex items-center gap-2">
            <flux:button wire:click="processQueue" wire:loading.attr="disabled" wire:target="processQueue">
                Process Queue
            </flux:button>
            @if ($failedMonitoredJobs > 0)
                <flux:button variant="warning" wire:click="retryFailed" wire:loading.attr="disabled" wire:target="retryFailed">
                    Retry Failed
                </flux:button>
            @endif
        </div>
    </div>

    {{-- Flash Messages --}}
    @if (session('success'))
        <div class="rounded-xl border border-green-200 bg-green-50 p-4 dark:border-green-800 dark:bg-green-950">
            <p class="text-sm text-green-800 dark:text-green-200">{{ session('success') }}</p>
        </div>
    @endif
    @if (session('error'))
        <div class="rounded-xl border border-red-200 bg-red-50 p-4 dark:border-red-800 dark:bg-red-950">
            <p class="text-sm text-red-800 dark:text-red-200">{{ session('error') }}</p>
        </div>
    @endif

    {{-- Stats Grid --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Pending Jobs</p>
            <p class="mt-2 text-3xl font-bold text-zinc-900 dark:text-zinc-100">{{ $pendingJobs }}</p>
            <p class="mt-1 text-xs text-zinc-400 dark:text-zinc-500">Waiting to be processed</p>
        </div>

        <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Running</p>
            <p class="mt-2 text-3xl font-bold text-blue-600 dark:text-blue-400">{{ $runningJobs }}</p>
            <p class="mt-1 text-xs text-zinc-400 dark:text-zinc-500">Currently executing</p>
        </div>

        <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Completed</p>
            <p class="mt-2 text-3xl font-bold text-green-600 dark:text-green-400">{{ $completedJobs }}</p>
            <p class="mt-1 text-xs text-zinc-400 dark:text-zinc-500">Successfully processed</p>
        </div>

        <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Failed</p>
            <p class="mt-2 text-3xl font-bold {{ $failedMonitoredJobs > 0 ? 'text-red-600 dark:text-red-400' : 'text-zinc-900 dark:text-zinc-100' }}">{{ $failedMonitoredJobs }}</p>
            <p class="mt-1 text-xs text-zinc-400 dark:text-zinc-500">Failed attempts</p>
        </div>
    </div>

    {{-- Performance Metrics --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Success Rate</p>
            <div class="mt-4 flex items-end gap-3">
                <span class="text-4xl font-bold text-zinc-900 dark:text-zinc-100">{{ $successRate }}</span>
                <span class="mb-1 text-lg text-zinc-400 dark:text-zinc-500">%</span>
            </div>
            <div class="mt-4 h-2 overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700">
                <div class="h-full rounded-full bg-green-500 transition-all" style="width: {{ $successRate }}%"></div>
            </div>
        </div>

        <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Avg Execution Time</p>
            <p class="mt-2 text-3xl font-bold text-zinc-900 dark:text-zinc-100">
                {{ $avgExecutionTime ? round($avgExecutionTime, 2) : 'N/A' }}
            </p>
            <p class="mt-1 text-xs text-zinc-400 dark:text-zinc-500">
                {{ $avgExecutionTime ? 'milliseconds (last 7 days)' : 'No data yet' }}
            </p>
        </div>
    </div>

    {{-- Recent Jobs Table --}}
    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="border-b border-zinc-200 bg-zinc-50 px-6 py-4 dark:border-zinc-700 dark:bg-zinc-800/50">
            <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Recent Jobs</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-800/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Job Name</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Queue</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Started</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse ($recentJobs as $job)
                        <tr wire:key="job-{{ $job->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-800/40">
                            <td class="px-4 py-3 text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                <span class="font-mono text-xs">{{ $job->name ?? 'Unknown' }}</span>
                            </td>
                            <td class="px-4 py-3 text-sm text-zinc-600 dark:text-zinc-400">{{ $job->queue ?? 'default' }}</td>
                            <td class="px-4 py-3 text-sm">
                                @if ($job->status === 'succeeded')
                                    <flux:badge color="green" size="sm">Success</flux:badge>
                                @elseif ($job->status === 'failed')
                                    <flux:badge color="red" size="sm">Failed</flux:badge>
                                @elseif ($job->status === 'running')
                                    <flux:badge color="blue" size="sm">Running</flux:badge>
                                @else
                                    <flux:badge size="sm">{{ $job->status ?? 'Unknown' }}</flux:badge>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-zinc-500 dark:text-zinc-400">
                                {{ $job->started_at?->diffForHumans() ?? 'N/A' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-10 text-center text-sm text-zinc-500 dark:text-zinc-400">
                                No jobs monitored yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Footer Actions --}}
    @if ($failedJobs > 0)
        <div class="flex items-center justify-between border-t border-zinc-200 pt-4 dark:border-zinc-700">
            <p class="text-sm text-zinc-600 dark:text-zinc-400">
                {{ $failedJobs }} {{ Str::plural('job', $failedJobs) }} failed in the queue.
            </p>
            <flux:button variant="danger" wire:click="flushFailed" wire:confirm="This will permanently remove all failed jobs. Are you sure?" wire:loading.attr="disabled" wire:target="flushFailed">
                Flush Failed Jobs
            </flux:button>
        </div>
    @else
        <p class="text-sm text-zinc-500 dark:text-zinc-400">Queue system is operating normally.</p>
    @endif
</div>
