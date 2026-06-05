<div
    class="overflow-x-auto"
    x-data="{
        collapsedCategories: [],
        defaultCollapsedCategories: @js($collapsedCategoryIds),
        storageKey: 'project-task-tree-collapsed-{{ $project->id }}',
        init() {
            this.loadCollapsedState();
        },
        loadCollapsedState() {
            try {
                const saved = window.localStorage.getItem(this.storageKey);
                const parsed = saved ? JSON.parse(saved) : null;

                if (Array.isArray(parsed)) {
                    this.collapsedCategories = parsed;

                    return;
                }
            } catch (error) {
                // Fall back to server defaults if localStorage is unavailable.
            }

            this.collapsedCategories = [...this.defaultCollapsedCategories];
        },
        persistCollapsedState() {
            try {
                window.localStorage.setItem(this.storageKey, JSON.stringify(this.collapsedCategories));
            } catch (error) {
                // Ignore storage failures (private mode, quota, etc.).
            }
        },
        isCollapsed(id) {
            return this.collapsedCategories.includes(id);
        },
        toggleCategory(id) {
            this.collapsedCategories = this.isCollapsed(id)
                ? this.collapsedCategories.filter(item => item !== id)
                : [...this.collapsedCategories, id];

            this.persistCollapsedState();
        }
    }"
>
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
                @foreach ($flatCategories as $categoryRow)
                    @include('tasks::livewire.admin.projects._task-category-tree-row', ['categoryRow' => $categoryRow])
                @endforeach

                @foreach ($uncategorizedTaskRows as $taskRow)
                    @include('tasks::livewire.admin.projects._task-hierarchy-task-row', ['taskRow' => $taskRow])
                @endforeach
            @else
                <tr>
                    <td colspan="6" class="px-3 py-8 text-center text-sm text-zinc-500 dark:text-zinc-400">No tasks found for this project.</td>
                </tr>
            @endif
        </tbody>
    </table>
</div>
