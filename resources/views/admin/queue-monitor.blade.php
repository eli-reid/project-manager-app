<x-layouts::admin :title="__('Queue Monitor')">
    <div class="space-y-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Header with Actions -->
            <div class="mb-6 flex justify-between items-center">
                <div>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ __('Monitor and manage background jobs') }}</h3>
                </div>
                <div class="flex gap-2">
                    <form method="POST" action="{{ route('admin.queue.process') }}" class="inline">
                        @csrf
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 dark:hover:bg-blue-600 text-sm font-medium">
                            {{ __('Process Queue') }}
                        </button>
                    </form>
                    @if($failedMonitoredJobs > 0)
                        <form method="POST" action="{{ route('admin.queue-monitor.retry') }}" class="inline">
                            @csrf
                            <button type="submit" class="px-4 py-2 bg-amber-600 text-white rounded-md hover:bg-amber-700 dark:hover:bg-amber-600 text-sm font-medium">
                                {{ __('Retry Failed') }}
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            @if($errors->any())
                <div class="mb-6 p-4 bg-red-50 dark:bg-red-950 border border-red-200 dark:border-red-900 rounded-lg">
                    <p class="text-sm font-medium text-red-800 dark:text-red-200">{{ __('Error') }}</p>
                    <p class="text-sm text-red-700 dark:text-red-300">{{ $errors->first() }}</p>
                </div>
            @endif

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <!-- Pending Jobs -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 border border-gray-200 dark:border-gray-700">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ __('Pending Jobs') }}</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-gray-100">{{ $pendingJobs }}</p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('Waiting to be processed') }}</p>
                </div>

                <!-- Running Jobs -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 border border-gray-200 dark:border-gray-700">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ __('Running') }}</p>
                    <p class="mt-2 text-3xl font-bold text-blue-600">{{ $runningJobs }}</p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('Currently executing') }}</p>
                </div>

                <!-- Completed Jobs -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 border border-gray-200 dark:border-gray-700">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ __('Completed') }}</p>
                    <p class="mt-2 text-3xl font-bold text-green-600">{{ $completedJobs }}</p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('Successfully processed') }}</p>
                </div>

                <!-- Failed Jobs -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 border border-gray-200 dark:border-gray-700">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ __('Failed') }}</p>
                    <p class="mt-2 text-3xl font-bold text-red-600">{{ $failedMonitoredJobs }}</p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('Failed attempts') }}</p>
                </div>
            </div>

            <!-- Performance Metrics -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <!-- Success Rate -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 border border-gray-200 dark:border-gray-700">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ __('Success Rate') }}</p>
                    <div class="mt-4 flex items-end gap-4">
                        <div class="text-4xl font-bold text-gray-900 dark:text-gray-100">{{ $successRate }}</div>
                        <div class="mb-1 text-lg text-gray-500 dark:text-gray-400">%</div>
                    </div>
                    <div class="mt-4 h-2 bg-gray-200 dark:bg-gray-600 rounded-full overflow-hidden">
                        <div class="h-full bg-green-600" style="width: {{ $successRate }}%"></div>
                    </div>
                </div>

                <!-- Avg Execution Time -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 border border-gray-200 dark:border-gray-700">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ __('Avg Execution Time') }}</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-gray-100">
                        {{ $avgExecutionTime ? round($avgExecutionTime, 2) : 'N/A' }}
                    </p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('milliseconds (last 7 days)') }}</p>
                </div>
            </div>

            <!-- Recent Jobs Table -->
            @if($recentJobs->count() > 0)
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ __('Recent Jobs') }}</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-400">{{ __('Job Name') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-400">{{ __('Queue') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-400">{{ __('Status') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-400">{{ __('Started') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($recentJobs as $job)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-900">
                                        <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                                            <span class="font-mono text-xs">{{ $job->name ?? __('Unknown') }}</span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">{{ $job->queue ?? 'default' }}</td>
                                        <td class="px-6 py-4 text-sm">
                                            @if($job->status === 'succeeded')
                                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">{{ __('Success') }}</span>
                                            @elseif($job->status === 'failed')
                                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">{{ __('Failed') }}</span>
                                            @elseif($job->status === 'running')
                                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">{{ __('Running') }}</span>
                                            @else
                                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200">{{ $job->status ?? __('Unknown') }}</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                            {{ $job->started_at ? $job->started_at->diffForHumans() : __('N/A') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @else
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-12 text-center">
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('No jobs monitored yet') }}</p>
                </div>
            @endif

            <!-- Actions Footer -->
            <div class="mt-6 flex items-center justify-between pt-6 border-t border-gray-200 dark:border-gray-700">
                @if($failedJobs > 0)
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        {{ __('Failed jobs in queue: :count', ['count' => $failedJobs]) }}
                    </p>
                    <form method="POST" action="{{ route('admin.queue-monitor.flush') }}" class="inline">
                        @csrf
                        <button type="submit" class="px-4 py-2 border border-red-600 text-red-600 dark:border-red-500 dark:text-red-400 rounded-md hover:bg-red-50 dark:hover:bg-red-950 text-sm font-medium">
                            {{ __('Flush Failed Jobs') }}
                        </button>
                    </form>
                @else
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('Queue system is operating normally') }}</p>
                    <div></div>
                @endif
            </div>
        </div>
    </div>
</x-layouts::admin>
