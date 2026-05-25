<div class="w-full space-y-6">
    <div class="flex items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-zinc-900 dark:text-zinc-100">Projects</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Track active and planned projects across your company.</p>
        </div>
        @can('create', \App\Domains\Projects\Models\Project::class)
            <a href="{{ route('admin.projects.create') }}" class="inline-flex items-center rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-700 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-300">Create Project</a>
        @endcan
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-800/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Name</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Project #</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Dates</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse ($projects as $project)
                        <tr
                            wire:key="project-{{ $project->id }}"
                            @click="if (! $event.target.closest('[data-prevent-row-nav]')) { window.Livewire?.navigate('{{ route('admin.projects.show', $project) }}') ?? window.location.assign('{{ route('admin.projects.show', $project) }}'); }"
                            class="cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-800/40"
                        >
                            <td class="px-4 py-3 align-top text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                <div>{{ $project->name }}</div>

                                @if ($project->isLeaveProject())
                                    <div class="mt-2 flex flex-wrap items-center gap-2 text-xs font-semibold uppercase tracking-wide">
                                        <span class="inline-flex rounded-md bg-amber-100 px-2 py-1 text-amber-800 dark:bg-amber-500/15 dark:text-amber-200">Timecard Leave</span>
                                        <span class="inline-flex rounded-md bg-zinc-100 px-2 py-1 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">{{ ucfirst($project->leave_category) }}</span>
                                    </div>
                                @endif
                            </td>
                            <td class="px-4 py-3 align-top text-sm text-zinc-700 dark:text-zinc-300">{{ $project->project_number ?? 'N/A' }}</td>
                            <td class="px-4 py-3 align-top text-sm text-zinc-700 dark:text-zinc-300">
                                <span class="inline-flex rounded-md bg-zinc-100 px-2 py-1 text-xs font-semibold text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">{{ $project->status?->label() ?? 'Unknown' }}</span>
                            </td>
                            <td class="px-4 py-3 align-top text-sm text-zinc-700 dark:text-zinc-300">
                                @if ($project->start_date || $project->end_date)
                                    {{ optional($project->start_date)?->format('M j, Y') ?? 'TBD' }}
                                    <span class="mx-1">to</span>
                                    {{ optional($project->end_date)?->format('M j, Y') ?? 'TBD' }}
                                @else
                                    TBD
                                @endif
                            </td>
                            <td class="px-4 py-3 align-top" data-prevent-row-nav x-on:click.stop="">
                                <livewire:ui.row-actions-dropdown label="Project actions" width="w-36" :menu-height="160">
                                    @can('view', $project)
                                        <a href="{{ route('admin.projects.show', $project) }}" wire:navigate class="block px-3 py-2 text-left text-sm text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800" @click="closeMenu()">View</a>
                                    @endcan
                                    @can('viewAny', \App\Domains\Tasks\Models\Task::class)
                                        <a href="{{ route('admin.tasks.index', ['project_id' => $project->id]) }}" wire:navigate class="block px-3 py-2 text-left text-sm text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800" @click="closeMenu()">Tasks</a>
                                    @endcan
                                    @can('update', $project)
                                        <a href="{{ route('admin.projects.edit', $project) }}" wire:navigate class="block px-3 py-2 text-left text-sm text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800" @click="closeMenu()">Edit</a>
                                    @endcan
                                </livewire:ui.row-actions-dropdown>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center text-sm text-zinc-500 dark:text-zinc-400">No projects found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-zinc-200 px-4 py-3 dark:border-zinc-700">
            {{ $projects->links() }}
        </div>
    </div>
</div>
