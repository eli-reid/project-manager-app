<section class="mx-auto w-full max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ __('Tasks') }}</flux:heading>
            <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Track assigned tasks with quick status and priority filters.') }}</flux:text>
        </div>
    </div>

    <div class="grid gap-3 md:grid-cols-3">
        <flux:field>
            <flux:label>{{ __('Project') }}</flux:label>
            <flux:select wire:model.live="projectFilter">
                <option value="">{{ __('All Projects') }}</option>
                @foreach ($projects as $project)
                    <option value="{{ $project->id }}">{{ $project->project_number ? $project->project_number.' - '.$project->name : $project->name }}</option>
                @endforeach
            </flux:select>
        </flux:field>

        <flux:field>
            <flux:label>{{ __('Status') }}</flux:label>
            <flux:select wire:model.live="statusFilter">
                <option value="">{{ __('All Statuses') }}</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status }}">{{ str($status)->replace('_', ' ')->headline() }}</option>
                @endforeach
            </flux:select>
        </flux:field>

        <flux:field>
            <flux:label>{{ __('Priority') }}</flux:label>
            <flux:select wire:model.live="priorityFilter">
                <option value="">{{ __('All Priorities') }}</option>
                @foreach ($priorities as $priority)
                    <option value="{{ $priority }}">{{ ucfirst($priority) }}</option>
                @endforeach
            </flux:select>
        </flux:field>
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-800/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Task') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Project') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Status') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Priority') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Due Date') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse ($tasks as $task)
                        <tr wire:key="user-task-{{ $task->id }}">
                            <td class="px-4 py-3 align-top">
                                <div class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $task->title }}</div>
                                @if (filled($task->description))
                                    <div class="mt-1 line-clamp-2 text-xs text-zinc-500 dark:text-zinc-400">{{ $task->description }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 align-top text-sm text-zinc-700 dark:text-zinc-300">{{ $task->project?->name ?? '—' }}</td>
                            <td class="px-4 py-3 align-top text-sm text-zinc-700 dark:text-zinc-300">{{ str($task->status)->replace('_', ' ')->headline() }}</td>
                            <td class="px-4 py-3 align-top text-sm text-zinc-700 dark:text-zinc-300">{{ ucfirst($task->priority) }}</td>
                            <td class="px-4 py-3 align-top text-sm text-zinc-700 dark:text-zinc-300">{{ $task->due_date?->format('M j, Y') ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center text-sm text-zinc-500 dark:text-zinc-400">{{ __('No tasks found for the selected filters.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-zinc-200 px-4 py-3 dark:border-zinc-700">
            {{ $tasks->links() }}
        </div>
    </div>
</section>
