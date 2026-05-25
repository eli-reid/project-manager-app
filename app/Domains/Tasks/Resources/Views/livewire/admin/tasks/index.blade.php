<div class="w-full space-y-6">
    <div class="flex items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-zinc-900 dark:text-zinc-100">Tasks</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Manage tasks within a selected project and category hierarchy.</p>
        </div>
        @can('create', \App\Domains\Tasks\Models\Task::class)
            <a href="{{ route('admin.tasks.create', ['project_id' => $projectFilter]) }}" wire:navigate class="inline-flex items-center rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-700 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-300">Create Task</a>
        @endcan
    </div>

    @if (session('success'))
        <div class="rounded-lg border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">{{ session('success') }}</div>
    @endif

    <div class="flex flex-wrap gap-3">
        <select wire:model.live="projectFilter" class="rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100">
            <option value="">All Projects</option>
            @foreach ($projects as $project)
                <option value="{{ $project->id }}">{{ $project->name }}</option>
            @endforeach
        </select>

        <select wire:model.live="statusFilter" class="rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100">
            <option value="">All Statuses</option>
            @foreach ($statuses as $status)
                <option value="{{ $status }}">{{ str($status)->replace('_', ' ')->headline() }}</option>
            @endforeach
        </select>
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-800/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Title</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Project</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Category</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Priority</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Assigned To</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Due</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse ($tasks as $task)
                        <tr wire:key="task-{{ $task->id }}">
                            <td class="px-4 py-3 align-top">
                                <div class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $task->title }}</div>
                                @if ($task->parentTask)
                                    <div class="mt-0.5 text-xs text-zinc-400">↳ {{ $task->parentTask->title }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 align-top text-sm text-zinc-700 dark:text-zinc-300">{{ $task->project?->name ?? '—' }}</td>
                            <td class="px-4 py-3 align-top text-sm text-zinc-700 dark:text-zinc-300">{{ $task->category?->name ?? '—' }}</td>
                            <td class="px-4 py-3 align-top text-sm">
                                @php
                                    $statusColors = [
                                        'todo' => 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300',
                                        'in_progress' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
                                        'completed' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
                                        'cancelled' => 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
                                    ];
                                @endphp
                                <span class="inline-flex rounded-md px-2 py-1 text-xs font-semibold {{ $statusColors[$task->status] ?? 'bg-zinc-100 text-zinc-700' }}">
                                    {{ str($task->status)->replace('_', ' ')->headline() }}
                                </span>
                            </td>
                            <td class="px-4 py-3 align-top text-sm">
                                @php
                                    $priorityColors = [
                                        'low' => 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400',
                                        'medium' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
                                        'high' => 'bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-300',
                                        'urgent' => 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
                                    ];
                                @endphp
                                <span class="inline-flex rounded-md px-2 py-1 text-xs font-semibold {{ $priorityColors[$task->priority] ?? 'bg-zinc-100 text-zinc-700' }}">
                                    {{ ucfirst($task->priority) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 align-top text-sm text-zinc-700 dark:text-zinc-300">
                                {{ $task->assignedTo ? $task->assignedTo->first_name . ' ' . $task->assignedTo->last_name : '—' }}
                            </td>
                            <td class="px-4 py-3 align-top text-sm text-zinc-700 dark:text-zinc-300">
                                {{ $task->due_date?->format('M j, Y') ?? '—' }}
                            </td>
                            <td class="px-4 py-3 align-top">
                                <livewire:ui.row-actions-dropdown label="Task actions" width="w-36" :menu-height="130">
                                    @can('update', $task)
                                        <a href="{{ route('admin.tasks.edit', $task) }}" wire:navigate class="block px-3 py-2 text-left text-sm text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800" @click="closeMenu()">Edit</a>
                                    @endcan
                                    @can('delete', $task)
                                        <button type="button" wire:click="deleteTask('{{ $task->id }}')" wire:confirm="Delete this task?" class="block w-full px-3 py-2 text-left text-sm text-red-700 hover:bg-red-50 dark:text-red-300 dark:hover:bg-red-900/30" @click="closeMenu()">Delete</button>
                                    @endcan
                                </livewire:ui.row-actions-dropdown>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-10 text-center text-sm text-zinc-500 dark:text-zinc-400">No tasks found.</td>
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
