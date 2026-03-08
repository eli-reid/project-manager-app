<div class="mx-auto w-full max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-zinc-900 dark:text-zinc-100">Scheduler Tasks</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Create and manage automated scheduler tasks.</p>
        </div>

        <a href="{{ route('admin.scheduler.tasks.create') }}" class="inline-flex items-center rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-700 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-300">
            New Task
        </a>
    </div>

    @if (session('success'))
        <div class="rounded-lg border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-700 dark:bg-red-900/30 dark:text-red-300">{{ session('error') }}</div>
    @endif

    <div class="grid gap-4 rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 sm:grid-cols-3">
        <div>
            <label for="task-search" class="mb-2 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Search</label>
            <input id="task-search" type="text" wire:model.live.debounce.300ms="search" placeholder="Task name or description" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100" />
        </div>

        <div>
            <label for="task-feature" class="mb-2 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Available Task</label>
            <select id="task-feature" wire:model.live="availableTask" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100">
                <option value="">All features</option>
                @foreach ($availableTasks as $availableTask)
                    <option value="{{ $availableTask->id }}">{{ $availableTask->name }} ({{ str($availableTask->feature_type)->replace('_', ' ')->headline() }})</option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="task-status" class="mb-2 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Status</label>
            <select id="task-status" wire:model.live="status" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100">
                <option value="all">All</option>
                <option value="active">Enabled</option>
                <option value="disabled">Disabled</option>
            </select>
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-800/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Task</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Schedule</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Next Run</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Runs</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse ($tasks as $task)
                        <tr wire:key="task-{{ $task->id }}">
                            <td class="px-4 py-3 align-top">
                                <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $task->name }}</div>
                                <div class="text-sm text-zinc-500 dark:text-zinc-400">{{ $task->availableTask?->name ?? 'Unknown Task Type' }}</div>
                                <div class="text-xs text-zinc-400 dark:text-zinc-500">{{ $task->availableTask?->feature_type ?? 'missing-feature-type' }}</div>
                            </td>
                            <td class="px-4 py-3 align-top text-sm text-zinc-700 dark:text-zinc-300">
                                <div>{{ app(\App\Core\Scheduler\Services\ScheduledTaskService::class)->formatScheduleDescription($task) }}</div>
                                @if ($task->is_enabled)
                                    <span class="mt-1 inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">Enabled</span>
                                @else
                                    <span class="mt-1 inline-flex rounded-full bg-red-100 px-2 py-0.5 text-xs font-semibold text-red-700 dark:bg-red-900/30 dark:text-red-300">Disabled</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 align-top text-sm text-zinc-700 dark:text-zinc-300">
                                {{ $task->next_run_at?->toDateTimeString() ?? 'Not scheduled' }}
                            </td>
                            <td class="px-4 py-3 align-top text-sm text-zinc-700 dark:text-zinc-300">{{ $task->run_count }}</td>
                            <td class="px-4 py-3 align-top">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.scheduler.tasks.edit', $task) }}" class="rounded-md border border-zinc-300 px-3 py-1.5 text-xs font-semibold uppercase tracking-wide text-zinc-700 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">Edit</a>
                                    <button type="button" wire:click="runNow('{{ $task->id }}')" wire:confirm="Run this task now?" wire:loading.attr="disabled" class="rounded-md border border-blue-300 px-3 py-1.5 text-xs font-semibold uppercase tracking-wide text-blue-700 hover:bg-blue-50 dark:border-blue-800 dark:text-blue-300 dark:hover:bg-blue-900/20">Run</button>
                                    <button type="button" wire:click="toggleEnabled('{{ $task->id }}')" wire:loading.attr="disabled" class="rounded-md border border-zinc-300 px-3 py-1.5 text-xs font-semibold uppercase tracking-wide text-zinc-700 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">
                                        {{ $task->is_enabled ? 'Disable' : 'Enable' }}
                                    </button>
                                    <button type="button" wire:click="deleteTask('{{ $task->id }}')" wire:confirm="Delete this task? This action cannot be undone." wire:loading.attr="disabled" class="rounded-md border border-red-300 px-3 py-1.5 text-xs font-semibold uppercase tracking-wide text-red-700 hover:bg-red-50 dark:border-red-800 dark:text-red-300 dark:hover:bg-red-900/20">Delete</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center text-sm text-zinc-500 dark:text-zinc-400">No scheduler tasks found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-zinc-200 px-4 py-3 dark:border-zinc-700">
            {{ $tasks->links() }}
        </div>
    </div>
</div>
