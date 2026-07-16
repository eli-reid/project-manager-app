<div class="overflow-x-auto">
    @if ($selectedItemCount > 0)
        <div class="mb-3 flex flex-wrap items-center justify-between gap-3 rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-800/40">
            <div class="text-sm text-zinc-700 dark:text-zinc-200">
                {{ $selectedItemCount }} item{{ $selectedItemCount === 1 ? '' : 's' }} selected
                <span class="text-zinc-500 dark:text-zinc-400">({{ $selectedCategoryCount }} categor{{ $selectedCategoryCount === 1 ? 'y' : 'ies' }}, {{ $selectedTaskCount }} task{{ $selectedTaskCount === 1 ? '' : 's' }})</span>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <button type="button" wire:click="bulkCopySelected" class="rounded-md border border-zinc-300 px-3 py-2 text-xs font-semibold text-zinc-700 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">Copy Selected</button>
                @if ($selectedTaskCount > 0)
                    <button type="button" wire:click="bulkMarkSelectedTasksComplete" class="rounded-md border border-emerald-300 px-3 py-2 text-xs font-semibold text-emerald-700 hover:bg-emerald-50 dark:border-emerald-700 dark:text-emerald-300 dark:hover:bg-emerald-900/30">Mark Tasks Complete</button>
                @endif
                <button type="button" wire:click="bulkDeleteSelected" wire:confirm="Delete the selected tasks and categories?" class="rounded-md border border-red-300 px-3 py-2 text-xs font-semibold text-red-700 hover:bg-red-50 dark:border-red-700 dark:text-red-300 dark:hover:bg-red-900/30">Delete Selected</button>
                <button type="button" wire:click="clearSelection" class="rounded-md border border-zinc-300 px-3 py-2 text-xs font-semibold text-zinc-700 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">Clear</button>
            </div>
        </div>
    @endif

    <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
        <thead class="bg-zinc-50 dark:bg-zinc-800/50">
            <tr>
                <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Name</th>
                <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Type</th>
                <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Status</th>
                <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Priority</th>
                <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Assigned</th>
                <th class="px-3 py-2 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
            @if ($hasTaskHierarchy)
                @foreach ($flatRows as $row)
                    @if ($row['type'] === 'category')
                        @include('tasks::livewire.admin.projects._task-category-tree-row', ['categoryRow' => $row['categoryRow']])
                    @else
                        @include('tasks::livewire.admin.projects._task-hierarchy-task-row', ['taskRow' => $row['taskRow']])
                    @endif
                @endforeach
            @else
                <tr>
                    <td colspan="6" class="px-3 py-8 text-center text-sm text-zinc-500 dark:text-zinc-400">No tasks found for this project.</td>
                </tr>
            @endif
        </tbody>
    </table>
</div>
