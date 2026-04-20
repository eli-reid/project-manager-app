<section class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
    <div class="mb-4 flex items-start justify-between gap-4">
        <div>
            <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                {{ __('Scheduler Health') }}
                <span class="ml-1.5 rounded-full bg-zinc-100 px-2 py-0.5 text-xs font-medium text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300">
                    {{ $tasks->count() }} {{ __('active') }}
                </span>
            </h3>
            <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Scheduled task status overview.') }}</p>
        </div>
        <a
            href="{{ route('admin.scheduler.tasks.index') }}"
            class="shrink-0 text-xs font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 dark:hover:text-indigo-300"
            wire:navigate
        >
            {{ __('Manage') }}
        </a>
    </div>

    @if ($statusCounts->has('failed') && $statusCounts['failed'] > 0)
        <div class="mb-3 rounded-lg border border-red-200 bg-red-50 px-3 py-2 dark:border-red-800 dark:bg-red-900/20">
            <p class="text-xs font-medium text-red-700 dark:text-red-400">
                {{ trans_choice(':count task failed|:count tasks failed', $statusCounts['failed'], ['count' => $statusCounts['failed']]) }}
            </p>
        </div>
    @endif

    @php
        $statusColors = [
            'idle'      => 'bg-zinc-100 text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300',
            'pending'   => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/60 dark:text-yellow-300',
            'running'   => 'bg-blue-100 text-blue-700 dark:bg-blue-900/60 dark:text-blue-300',
            'completed' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/60 dark:text-emerald-300',
            'failed'    => 'bg-red-100 text-red-700 dark:bg-red-900/60 dark:text-red-300',
        ];
    @endphp

    @forelse ($tasks as $task)
        <div class="flex items-center justify-between rounded-lg px-3 py-2 hover:bg-zinc-50 dark:hover:bg-zinc-800/40">
            <div class="min-w-0">
                <p class="truncate text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $task->name }}</p>
                @if ($task->next_run_at)
                    <p class="truncate text-xs text-zinc-500 dark:text-zinc-400">
                        {{ __('Next:') }} {{ $task->next_run_at->diffForHumans() }}
                    </p>
                @else
                    <p class="text-xs text-zinc-400 dark:text-zinc-500">{{ __('Not scheduled') }}</p>
                @endif
            </div>
            <div class="ml-3 shrink-0">
                @php $status = $task->runtime_status['status'] ?? 'idle'; @endphp
                <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $statusColors[$status] ?? $statusColors['idle'] }}">
                    {{ str($status)->headline() }}
                </span>
            </div>
        </div>
    @empty
        <p class="py-4 text-center text-sm text-zinc-500 dark:text-zinc-400">{{ __('No active scheduled tasks.') }}</p>
    @endforelse
</section>
