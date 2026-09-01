<div class="py-1">
    <button
        type="button"
        x-show="contextMenuCanCreateTask"
        @click="const id = contextMenuId; closeContextMenu(); if (id) { $wire.startInlineTaskForm(id); }"
        class="block w-full px-3 py-2 text-left text-xs text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800"
    >
        Quick Add Task
    </button>
    <button
        type="button"
        x-show="contextMenuCanCreateCategory"
        @click="const id = contextMenuId; closeContextMenu(); if (id) { $wire.startInlineCategoryForm(id); }"
        class="block w-full px-3 py-2 text-left text-xs text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800"
    >
        Quick Add Subcategory
    </button>
    <button
        type="button"
        x-show="contextMenuCanUpdate"
        @click="const id = contextMenuId; closeContextMenu(); if (id) { $wire.startEditCategoryName(id); }"
        class="block w-full px-3 py-2 text-left text-xs text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800"
    >
        Rename Category
    </button>
    <button
        type="button"
        x-show="contextMenuCanUpdate"
        @click="const id = contextMenuId; closeContextMenu(); if (id) { $wire.moveCategory(id, 'up'); }"
        class="block w-full px-3 py-2 text-left text-xs text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800"
    >
        Move Up
    </button>
    <button
        type="button"
        x-show="contextMenuCanUpdate"
        @click="const id = contextMenuId; closeContextMenu(); if (id) { $wire.moveCategory(id, 'down'); }"
        class="block w-full px-3 py-2 text-left text-xs text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800"
    >
        Move Down
    </button>
    <button
        type="button"
        @click="const id = contextMenuId; closeContextMenu(); if (id) { $wire.copyCategoryFrom(id); }"
        class="block w-full px-3 py-2 text-left text-xs text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800"
    >
        Copy Category
    </button>
    <button
        type="button"
        @click="const id = contextMenuId; closeContextMenu(); if (id) { $wire.$set('copyCategorySourceId', id); showCopyModal = true; }"
        class="block w-full px-3 py-2 text-left text-xs text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800"
    >
        Copy Category Tasks
    </button>
    <button
        type="button"
        x-show="contextMenuCanCreateTemplate"
        @click="const id = contextMenuId; closeContextMenu(); if (id) { $wire.startSaveCategoryAsTemplate(id); }"
        class="block w-full px-3 py-2 text-left text-xs text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800"
    >
        Save as Template
    </button>
    <button
        type="button"
        x-show="contextMenuCanDelete"
        @click="const id = contextMenuId; if (id && confirm('Delete this category branch? This deletes the category, all subcategories, and all tasks in that branch.')) { closeContextMenu(); $wire.deleteCategory(id); }"
        class="block w-full px-3 py-2 text-left text-xs text-red-700 hover:bg-red-50 dark:text-red-300 dark:hover:bg-red-900/30"
    >
        Delete Category
    </button>
</div>
