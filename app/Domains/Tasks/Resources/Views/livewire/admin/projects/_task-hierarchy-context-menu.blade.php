<div
    x-show="contextMenuOpen"
    x-cloak
    x-on:click.stop=""
    class="fixed z-40 w-48 overflow-hidden rounded-lg border border-zinc-200 bg-white shadow-lg dark:border-zinc-700 dark:bg-zinc-900"
    :style="'top: ' + contextMenuY + 'px; left: ' + contextMenuX + 'px;'"
>
    <template x-if="contextMenuType === 'category'">
        @include('tasks::livewire.admin.projects._task-hierarchy-context-menu-category')
    </template>

    <template x-if="contextMenuType === 'task'">
        @include('tasks::livewire.admin.projects._task-hierarchy-context-menu-task')
    </template>
</div>
