<div
    class="relative"
    x-data="buildMenuState(220, 6)"
    @click.away="closeMenu()"
>
    <button type="button" @click="toggleMenu($event)" class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-zinc-300 text-zinc-600 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800" aria-label="Task actions">
        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <circle cx="4" cy="10" r="1.5" />
            <circle cx="10" cy="10" r="1.5" />
            <circle cx="16" cy="10" r="1.5" />
        </svg>
    </button>

    <div x-show="open" x-cloak class="fixed z-30 w-56 overflow-hidden rounded-lg border border-zinc-200 bg-white shadow-lg dark:border-zinc-700 dark:bg-zinc-900" :style="menuStyle">
        @if ($canCreateTask)
            <button type="button" @click="closeMenu()" wire:click="startInlineTaskForm" class="block w-full px-3 py-2 text-left text-sm text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800">Quick Add Task</button>
        @endif
        @if ($canCreateTaskCategory)
            <button type="button" @click="closeMenu()" wire:click="startInlineCategoryForm" class="block w-full px-3 py-2 text-left text-sm text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800">Quick Add Category</button>
        @endif
        @if ($canCreateTask)
            <div class="my-1 border-t border-zinc-200 dark:border-zinc-700"></div>
            <a href="{{ route('admin.tasks.create', ['project_id' => $project->id]) }}" wire:navigate class="block px-3 py-2 text-sm text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800">Add Task</a>
        @endif
        @if ($canCreateTaskCategory)
            <a href="{{ route('admin.task-categories.create', ['project_id' => $project->id]) }}" wire:navigate class="block px-3 py-2 text-sm text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800">Add Category</a>
        @endif
        @if ($canCreateTaskCategory)
            <button type="button" @click="closeMenu(); showCopyCategoryModal = true" class="block w-full px-3 py-2 text-left text-sm text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800">Copy Category</button>
        @endif
        @if ($canCreateTask)
            <button type="button" @click="closeMenu(); showCopyModal = true" class="block w-full px-3 py-2 text-left text-sm text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800">Copy Category Tasks</button>
        @endif
    </div>
</div>
