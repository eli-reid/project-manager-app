<div class="py-1">
    <button
        type="button"
        x-show="contextMenuCanUpdate"
        @click="const id = contextMenuId; closeContextMenu(); if (id) { $wire.startEditTaskTitle(id); }"
        class="block w-full px-3 py-2 text-left text-xs text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800"
    >
        Rename Task
    </button>
    <button
        type="button"
        x-show="contextMenuCanUpdate"
        @click="const id = contextMenuId; closeContextMenu(); if (id) { $wire.moveTask(id, 'up'); }"
        class="block w-full px-3 py-2 text-left text-xs text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800"
    >
        Move Up
    </button>
    <button
        type="button"
        x-show="contextMenuCanUpdate"
        @click="const id = contextMenuId; closeContextMenu(); if (id) { $wire.moveTask(id, 'down'); }"
        class="block w-full px-3 py-2 text-left text-xs text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800"
    >
        Move Down
    </button>
    <button
        type="button"
        x-show="contextMenuCanCreateTask"
        @click="const id = contextMenuId; closeContextMenu(); if (id) { $wire.copyTaskFrom(id); }"
        class="block w-full px-3 py-2 text-left text-xs text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800"
    >
        Copy Task
    </button>
    <button
        type="button"
        x-show="contextMenuCanUpdateStatus"
        @click="const id = contextMenuId; closeContextMenu(); if (id) { $wire.markTaskComplete(id); }"
        class="block w-full px-3 py-2 text-left text-xs text-emerald-700 hover:bg-emerald-50 dark:text-emerald-300 dark:hover:bg-emerald-900/30"
    >
        Mark Complete
    </button>
    <button
        type="button"
        x-show="contextMenuCanDelete"
        @click="const id = contextMenuId; if (id && confirm('Delete this task?')) { closeContextMenu(); $wire.deleteTask(id); }"
        class="block w-full px-3 py-2 text-left text-xs text-red-700 hover:bg-red-50 dark:text-red-300 dark:hover:bg-red-900/30"
    >
        Delete Task
    </button>
</div>
